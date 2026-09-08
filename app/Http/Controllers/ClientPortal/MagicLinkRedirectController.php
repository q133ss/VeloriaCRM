<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MagicLinkRedirectController extends Controller
{
    /**
     * Browser landing page for the magic-link email's https App Link.
     *
     * Android opens the app directly here when the App Link is verified
     * (see /.well-known/assetlinks.json); everyone else — unverified Android,
     * a desktop mail client, a link preview bot — lands on this page, which
     * immediately retries via the custom `veloriaclient://` scheme and shows
     * the code as a manual fallback.
     */
    public function __invoke(Request $request): View
    {
        $verificationId = (string) $request->query('vid', '');
        $code = (string) $request->query('code', '');

        $appSchemeLink = sprintf(
            '%s://auth/verify?vid=%s&code=%s',
            config('services.client_portal.mobile_scheme'),
            $verificationId,
            $code,
        );

        return view('client_portal.magic_link_redirect', [
            'appSchemeLink' => $appSchemeLink,
            'code' => $code,
        ]);
    }
}
