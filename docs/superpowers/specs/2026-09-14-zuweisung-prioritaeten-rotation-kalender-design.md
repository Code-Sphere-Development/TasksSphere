# Zuweisung, Prioritäten, Rotation und Kalender

Stand: 2026-09-14

## Kontext

TasksSphere soll familientauglich werden — ausgelöst durch den Wunsch, dass es „ähnlich wie Donetick" funktioniert — und später in **FamilyNetwork** eingebettet werden können, ohne seine Eigenständigkeit zu verlieren.

Die Architektur des Ökosystems (`Code-Sphere-Development/Code-Sphere-Arch`, `Structure.md`) gibt dabei den Rahmen vor: jede Datenart hat genau einen besitzenden Dienst. Konten gehören **AccountSphere**, die Definition einer Familie gehört **FamilyNetwork**, Aufgaben gehören **TasksSphere**. Donetick bringt sein eigenes Benutzer-, Gruppen- und Einladungssystem mit — genau dieser Teil wird hier **nicht** nachgebaut, weil er im Ökosystem schon jemandem gehört.

Was bleibt, deckt sich mit `tasksphere_prd.md`: dort stehen `task_assignments (task_id, user_id, assigned_by)` bereits im Datenmodell und **Aufgabenprioritäten** unter den Kernfunktionen. Beides fehlt im Code. (Das PRD hinkt umgekehrt hinterher: wiederkehrende Aufgaben stehen dort unter „zukünftige Funktionen" und sind längst fertig.)

## Getroffene Entscheidungen

| Frage | Entscheidung |
|---|---|
| Wem gehört die Identität? | Konten → AccountSphere, Familie → FamilyNetwork |
| Wann andocken? | Noch nicht. Lokal bauen, aber hinter einer Naht, die später getauscht wird |
| Woher kommt der Kreis? | Vorerst über die E-Mail-Adresse, später aus FamilyNetwork |
| Was zuerst? | Prioritäten, Zuweisung, Rotation, Kalenderansicht |

**Gesetzte Annahme:** „über E-Mail" heißt *eine Person anhand ihrer Adresse finden und hinzufügen*. Es gibt in TasksSphere keine Mailinfrastruktur (`MAIL_MAILER=log`, keine Mail-Notification, kein Mailable), also keine Einladungsmails an Personen ohne Konto. Wer hinzugefügt werden soll, braucht ein TasksSphere-Konto. Echte Einladungen wären ein eigenes Vorhaben.

## Die Naht

Alles, was wissen muss, *welche Menschen es gibt*, geht über ein Interface — nie direkt an eine Tabelle:

```php
namespace App\Support\People;

interface PeopleDirectory
{
    /** Personen, denen der Nutzer Aufgaben zuweisen darf. */
    public function assignableFor(User $user): Collection;

    /** Darf $actor dem $candidate eine Aufgabe zuweisen? */
    public function canAssign(User $actor, User $candidate): bool;
}
```

Heute dahinter `LocalPeopleDirectory` (liest den Haushalt aus der eigenen Datenbank), später `FamilyNetworkDirectory` (fragt FamilyNetwork über `external_ref`). Gebunden im Service Container, umgeschaltet über `config/tasks.php`. Zuweisung, Rotation und Kalenderfilter kennen ausschließlich das Interface.

Das ist der ganze Zweck der Übung: Wenn FamilyNetwork kommt, wird eine Implementierung ausgetauscht und ein Verweisfeld gefüllt — kein Umbau.

## Datenmodell

Vier Migrationen, alle additiv.

```
households                      household_user
-----                           -----
id                              household_id
name                            user_id
owner_id      -> users          role          (owner|member)
external_ref  nullable          timestamps
timestamps                      unique(household_id, user_id)
   ^ spaeter: FamilyNetwork family id

task_assignments                tasks  (neue Spalten)
-----                           -----
id                              priority            nullable smallint
task_id       -> tasks          assigned_to         nullable -> users
user_id       -> users          rotation_strategy   nullable string(24)
assigned_by   -> users
timestamps                      task_completions  (neue Spalte)
unique(task_id, user_id)        -----
                                completed_by        nullable -> users
```

`task_assignments` ist der **Kreis möglicher Zuständiger** je Aufgabe (Donetick: „Assignees"), `tasks.assigned_to` die **aktuell zuständige Person** („Assigned"). Die Spaltennamen folgen dem PRD.

`task_completions.completed_by` fehlt heute — ohne sie lässt sich weder „wer hat erledigt" anzeigen noch die Rotationsstrategie *am seltensten erledigt* berechnen.

## Die vier Bausteine

Jeder ist für sich lieferbar. Reihenfolge ergibt sich aus den Abhängigkeiten.

### 1. Prioritäten

Steht im PRD unter Kernfunktionen, fehlt vollständig. Kleinstes Stück, keine Abhängigkeit.

`tasks.priority` als nullable Kleinzahl mit vier Stufen (analog Donetick P1–P4), Auswahlfeld im Formular, farbige Markierung auf der Karte, Sortier- und Filterkriterium. Fügt sich direkt in das Filtervorhaben des freigegebenen Plans ein — sollte deshalb zusammen mit oder nach diesem landen.

API: `priority` optional in `POST`/`PUT /api/tasks` ergänzen, rein additiv.

### 2. Haushalt und Zuweisung

Der Kern. Ohne ihn ist nichts familientauglich.

- Haushalt anlegen, Mitglieder über ihre E-Mail-Adresse hinzufügen und entfernen (Besitzer only). Unbekannte Adresse → Validierungsfehler, keine Mail.
- Je Aufgabe ein Kreis möglicher Zuständiger aus dem Haushalt, plus eine aktuell zuständige Person.
- `LocalPeopleDirectory` liest die Mitglieder des Haushalts, dem der Nutzer angehört.

**Drei Stellen, die dabei ihr Verhalten ändern und einzeln bedacht werden müssen:**

*Erinnerungen.* `SendTaskReminders` benachrichtigt heute `$task->user`. Künftig muss die **zuständige** Person benachrichtigt werden, ersatzweise der Besitzer. Das ist eine echte Verhaltensänderung am produktiven Push-Pfad.

*Sichtbarkeit.* Dashboard und `GET /api/tasks` gehen über `Auth::user()->tasks()`, also über den Besitz. Eine zugewiesene Aufgabe gehört aber weiterhin dem Ersteller. Ohne Eingriff sieht die zuständige Person ihre Aufgabe nirgends. Lösung: ein Scope `Task::forPerson($userId)` = besitzt **oder** ist zugewiesen, verdrahtet im Dashboard. `GET /api/tasks` behält seine heutige Bedeutung („meine Aufgaben") und bekommt einen **optionalen** Parameter für den erweiterten Blick — der produktive Gehirn-Agent hängt an diesem Endpunkt und darf seine Antwort nicht stillschweigend verändert bekommen.

*Rechte.* `TaskPolicy` muss die zuständige Person einbeziehen: erledigen und bearbeiten ja, löschen bleibt beim Besitzer. Wie schon bei der Listenfreigabe erwogen, jetzt auf der Zuweisungsachse.

### 3. Rotation

Setzt Baustein 2 voraus. Vier Strategien wie bei Donetick: zufällig, am seltensten zugewiesen, am seltensten erledigt, letzter bleibt.

Der Einhängepunkt existiert bereits: `Task::complete()` schiebt bei wiederkehrenden Aufgaben ohnehin `due_at` weiter. Genau dort wird zusätzlich die nächste zuständige Person aus dem Kreis bestimmt. „Am seltensten erledigt" braucht `task_completions.completed_by`, „am seltensten zugewiesen" eine Zählung über die bisherigen Zuweisungen.

Als eigene Klasse hinter einem Interface (`RotationStrategy`), damit jede Strategie für sich testbar ist und `Task` nicht weiter wächst — das Modell ist mit über 350 Zeilen schon das größte im Projekt.

### 4. Kalenderansicht

Unabhängig von 1–3, kann jederzeit landen.

Monats- und Tagesblick auf denselben Occurrence-Daten, die das Dashboard schon erzeugt. Farbe nach Priorität oder zuständiger Person, Filter nach Person. Eigene Route und eigene Livewire-Komponente — nicht noch mehr Fläche auf `TaskManager`.

Zu beachten: `Task::getOccurrences()` läuft je Aufgabe in PHP mit einer Schleifengrenze von 100. Ein Monatsfenster ist deutlich größer als das heutige Sieben-Tage-Fenster; bei stündlichen Wiederholungen greift die Grenze. Vor dem Bau messen, nicht hoffen.

## Auswirkung auf den freigegebenen Plan

- **0c (toter Team-Code) wird zur Voraussetzung.** `team_id`, `TaskList::team()` und die `currentTeam`-Phantome müssen weg, bevor der Haushalt kommt — sonst stehen drei Gruppenkonzepte nebeneinander.
- **Vorhaben 1–4 bleiben unverändert** (Rückgängig, Liste wählen, Filter, Archiv). Prioritäten gehen mit dem Filtervorhaben zusammen.
- **Vorhaben 5 „Listen per E-Mail teilen" entfällt.** Die Listenfreigabe war die richtige Antwort auf die frühere Fragestellung, ist aber die falsche Abstraktion für das Ökosystem. Zuweisung ersetzt sie.

## Bewusst nicht enthalten

Punkte und Bestenlisten, Unteraufgaben, Labels, Vorlagen, natürlichsprachige Eingabe, mehrstufige Erinnerungen, adaptive Wiederholung, Telegram und Webhooks, Auswertungen, Import/Export, zustandsbasierte Auslöser, Tablet-Kiosk — alles aus Donetick, alles denkbar, nichts davon jetzt. Ebenso: die Anbindung an AccountSphere und FamilyNetwork selbst, echte Einladungsmails, und ein Rechtemodell für den Gehirn-Agenten (am 2026-09-11 abgelehnt, der Agent behält Vollrechte).

## Entschieden bei der Umsetzung

1. „Über E-Mail" heißt: eine Person anhand ihrer Adresse **finden**. Echte Einladungen an Personen ohne Konto bleiben ein eigenes Vorhaben.
2. Eine Person **darf** in mehreren Haushalten sein. `LocalPeopleDirectory` vereinigt deren Mitglieder.
3. `GET /api/tasks` behält seine Bedeutung. Zugewiesene Aufgaben liefert es nur auf `?include=assigned`; ein Test sichert das ab, weil der Gehirn-Agent daran hängt.

## Was die Umsetzung zusätzlich zutage gefördert hat

**Die Terminobergrenze war ein Bestandsfehler, kein Kalenderproblem.** Der Entwurf vermutete, ein Monatsfenster könnte an die Grenze von 100 stoßen. Die Messung zeigte: sie greift **schon heute**. Eine stündliche Aufgabe müsste über das Sieben-Tage-Fenster des Dashboards 168 Termine liefern und lieferte 100 — über ein Drittel fehlte stillschweigend, in der Weboberfläche wie in `GET /api/tasks/occurrences`. Die Grenze ist jetzt konfigurierbar und auf 750 gesetzt.

**`task_completions.completed_by` war Voraussetzung, nicht Beiwerk.** Ohne die Spalte lässt sich die Rotationsstrategie „am seltensten erledigt" gar nicht berechnen.

## Noch offen

- Punkte und Bestenlisten, Unteraufgaben, Labels, Vorlagen, natürlichsprachige Eingabe, mehrstufige Erinnerungen, adaptive Wiederholung, Telegram und Webhooks, Auswertungen, Import/Export, Tablet-Kiosk.
- Die Anbindung an AccountSphere und FamilyNetwork selbst. Die Naht dafür steht: `PeopleDirectory` und `households.external_ref`.
- Aus dem früheren Plan weiterhin offen: Erledigt rückgängig machen, Liste beim Anlegen wählen, Suchen und Filtern, Archiv und Papierkorb.
