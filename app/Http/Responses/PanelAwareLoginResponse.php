<?php

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Livewire\Features\SupportRedirects\Redirector;

class PanelAwareLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        /** @var Request $request */
        $panel = $this->resolvePanel($request);
        $intendedUrl = $request->session()->pull('url.intended');

        if (is_string($intendedUrl) && $this->urlBelongsToPanel($intendedUrl, $panel, $request)) {
            return redirect()->to($intendedUrl);
        }

        return redirect()->to($panel->getUrl() ?? url($panel->getPath()));
    }

    private function resolvePanel(Request $request): Panel
    {
        $intendedUrl = $request->session()->get('url.intended');
        $candidateUrls = [
            $request->headers->get('referer'),
            is_string($intendedUrl) ? $intendedUrl : null,
        ];

        foreach ($candidateUrls as $candidateUrl) {
            if (! is_string($candidateUrl)) {
                continue;
            }

            foreach (Filament::getPanels() as $panel) {
                if ($this->urlBelongsToPanel($candidateUrl, $panel, $request)) {
                    return $panel;
                }
            }
        }

        return Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
    }

    private function urlBelongsToPanel(string $url, Panel $panel, Request $request): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($host) && strcasecmp($host, $request->getHost()) !== 0) {
            return false;
        }

        $path = '/'.trim((string) parse_url($url, PHP_URL_PATH), '/');
        $panelPath = '/'.trim($panel->getPath(), '/');

        return $path === $panelPath || str_starts_with($path, "{$panelPath}/");
    }
}
