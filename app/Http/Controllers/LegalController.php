<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Impressum und Datenschutzerklaerung.
 *
 * Die Texte liegen als Markdown unter resources/markdown, damit sie ohne
 * Code-Aenderung gepflegt werden koennen - dasselbe Muster, das Jetstream fuer
 * seine Nutzungsbedingungen verwendet.
 */
class LegalController extends Controller
{
    public function imprint(): View
    {
        return $this->render('impressum', __('Impressum'));
    }

    public function privacy(): View
    {
        return $this->render('datenschutz', __('Datenschutz'));
    }

    private function render(string $file, string $title): View
    {
        $markdown = file_get_contents(resource_path("markdown/{$file}.md"));

        return view('legal', [
            'title' => $title,
            // Ein Zeilenumbruch im Markdown soll auch einer bleiben: Anschriften
            // stehen sonst in einer Zeile, weil Markdown einfache Umbrueche zu
            // Leerzeichen zusammenzieht.
            'content' => Str::markdown($markdown, ['renderer' => ['soft_break' => "<br>\n"]]),
        ]);
    }
}
