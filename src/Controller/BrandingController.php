<?php
declare(strict_types=1);

namespace App\Controller;

use App\Portal\Profile;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * The logos, icons and colours of the operator's profile, which lives outside the web root.
 */
class BrandingController extends AppController
{
    protected array $open = ['asset'];

    /**
     * @param string $file A plain file name in the profile's assets/.
     * @return \Cake\Http\Response
     */
    public function asset(string $file): Response
    {
        $path = $this->profile->asset($file);
        $type = Profile::assetType($file);
        if ($path === null || $type === null) {
            throw new NotFoundException();
        }

        return $this->getResponse()
            ->withHeader('Content-Type', $type)
            ->withFile($path)
            ->withHeader('Cache-Control', 'public, max-age=3600')
            // an SVG opened on its own runs nothing
            ->withHeader(
                'Content-Security-Policy',
                "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox",
            );
    }
}
