<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Statamic\Facades\Asset;
use Statamic\Facades\Entry;

/**
 * The Dog-ear Books catalogue and journal, with cover art and journal
 * pictures drawn here (so the demo needs no photos), for the social images
 * to show. Run it again to put everything back.
 */
class DemoContent extends Command
{
    protected $signature = 'demo:content';

    protected $description = 'Make the demo shop\'s books, journal and pictures (local only)';

    protected const FONT = '/System/Library/Fonts/Supplemental/Georgia.ttf';

    protected const BOOKS = [
        ['the-salt-orchard', 'The Salt Orchard', 'Imogen Hale', '14.00', '#2F5D62', '#F2E8CF', 'Three sisters inherit an orchard that grows only in sea air.'],
        ['coral-arithmetic', 'Coral Arithmetic', 'Dev Okafor', '13.00', '#D9725B', '#1B2433', 'A marine biologist counts what\'s left of a reef, and what isn\'t.'],
        ['ninety-blue-kites', 'Ninety Blue Kites', 'Sora Lindqvist', '9.99', '#3B6EA5', '#F4F1EA', 'A village flies a kite for every child who left. One comes back.'],
        ['the-paper-cartographer', 'The Paper Cartographer', 'Agnes Moreau', '16.50', '#E4C590', '#2B2118', 'Maps of places that don\'t exist yet, drawn by a girl who can\'t leave home.'],
        ['moss-on-the-northern-wall', 'Moss on the Northern Wall', 'Callum Reid', '12.00', '#4E6B3A', '#EEF0E5', 'Poems from a year spent watching one wall in the Highlands.'],
        ['the-lantern-ledger', 'The Lantern Ledger', 'Priya Natarajan', '15.00', '#7A2E3A', '#F6E7C8', 'An accountant in 1890s Bombay finds a ledger that balances itself.'],
        ['small-weathers', 'Small Weathers', 'Lena Fischer', '10.50', '#9BB7C4', '#1E2A30', 'Stories no longer than a rain shower, collected over ten years.'],
        ['under-the-copper-roof', 'Under the Copper Roof', 'Tomás Ibarra', '17.00', '#B5652B', '#FFF4E6', 'A family saga told by the house they lived in.'],
        ['seven-ways-to-boil-the-sea', 'Seven Ways to Boil the Sea', 'Hana Sato', '13.50', '#1F3B4D', '#E9D985', 'A cook\'s memoir of the ships she fed and the storms they met.'],
        ['notes-from-a-lighthouse', 'Notes from a Lighthouse', 'Owen Pryce', '11.00', '#E8E2D0', '#26323A', 'The last keeper of a Welsh light writes to whoever comes next.'],
        ['the-quiet-apprentice', 'The Quiet Apprentice', 'Mei Chen', '12.50', '#5B4B8A', '#F1EDF8', 'A clockmaker\'s apprentice who never speaks hears everything.'],
        ['a-field-guide-to-leaving', 'A Field Guide to Leaving', 'Ruth Adeyemi', '14.50', '#C9D6A3', '#2E3B1F', 'Birds, trains and the people who take them, catalogued with care.'],
    ];

    protected const JOURNAL = [
        ['autumn-reading-list', 'Our autumn reading list: twelve books for longer evenings', 'mira', '2026-10-02', 'Short days, long books. The shop\'s favourites for the season, from sea sagas to small poems.', ['#B5652B', '#F2C27B']],
        ['in-conversation-with-imogen-hale', 'In conversation with Imogen Hale', 'tobias', '2026-09-18', 'The author of The Salt Orchard on sea air, sisters and why every orchard is a kind of argument.', ['#2F5D62', '#9BC4BC']],
        ['why-we-dog-ear', 'Why we dog-ear (and why you should too)', 'june', '2026-09-03', 'A defence of folding the corner, from the shop that named itself after it.', ['#7A2E3A', '#E4C590']],
        ['the-shop-cat-has-opinions', 'The shop cat has opinions', 'mira', '2026-08-21', 'Biscuit\'s five-star picks, chosen by where she chooses to sleep.', ['#5B4B8A', '#C9B8F0']],
        ['making-the-paper-cartographer-cover', 'Making the cover of The Paper Cartographer', 'tobias', '2026-07-30', 'How a hand-drawn map became the year\'s most photographed book jacket.', ['#E4C590', '#3B6EA5']],
        ['events', 'Events', null, '2026-07-01', null, ['#1F3B4D', '#9BB7C4']],
    ];

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->components->error('demo:content replaces the demo\'s books and journal, so it only runs on local sites.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(public_path('assets/covers'));
        File::ensureDirectoryExists(public_path('assets/journal'));

        foreach (self::BOOKS as $i => [$slug, $title, $byline, $price, $cover, $ink, $blurb]) {
            $this->cover("covers/{$slug}.png", $title, $byline, $cover, $ink);

            $entry = Entry::query()->where('collection', 'books')->where('slug', $slug)->first() ?? Entry::make()->collection('books')->slug($slug);
            $entry->data([
                'title' => $title,
                'byline' => $byline,
                'price' => $price,
                'blurb' => $blurb,
                'cover_image' => "covers/{$slug}.png",
            ])->save();

            $this->components->twoColumnDetail('Book', $title);
        }

        foreach (self::JOURNAL as [$slug, $title, $author, $date, $excerpt, $colours]) {
            $this->illustration("journal/{$slug}.png", ...$colours);

            $entry = Entry::query()->where('collection', 'journal')->where('slug', $slug)->first() ?? Entry::make()->collection('journal')->slug($slug);
            $entry->date($date)->data(array_filter([
                'title' => $title,
                'author' => $author,
                'excerpt' => $excerpt,
                // The events post has no picture, to show the fallback.
                'featured_image' => $slug === 'events' ? null : "journal/{$slug}.png",
                'content' => $excerpt ? $excerpt."\n\nMore soon from the shop floor." : 'Readings and signings at the shop, every first Thursday.',
            ]))->save();

            $this->components->twoColumnDetail('Journal', $title);
        }

        return self::SUCCESS;
    }

    /**
     * A plain cloth-bound cover: a colour, the title and the author.
     */
    protected function cover(string $path, string $title, string $author, string $background, string $ink): void
    {
        [$w, $h] = [800, 1200];
        $image = imagecreatetruecolor($w, $h);
        $bg = $this->colour($image, $background);
        $fg = $this->colour($image, $ink);
        imagefill($image, 0, 0, $bg);
        imagesetthickness($image, 4);
        imagerectangle($image, 48, 48, $w - 49, $h - 49, $fg);

        $lines = explode("\n", wordwrap($title, 14));
        $y = 380;
        foreach ($lines as $line) {
            $box = imagettfbbox(64, 0, self::FONT, $line);
            imagettftext($image, 64, 0, (int) (($w - ($box[2] - $box[0])) / 2), $y, $fg, self::FONT, $line);
            $y += 96;
        }

        $box = imagettfbbox(30, 0, self::FONT, $author);
        imagettftext($image, 30, 0, (int) (($w - ($box[2] - $box[0])) / 2), $h - 140, $fg, self::FONT, $author);

        $this->save($image, $path);
    }

    /**
     * A soft abstract picture in two colours, standing in for a photo.
     */
    protected function illustration(string $path, string $from, string $to): void
    {
        [$w, $h] = [1600, 1000];
        $image = imagecreatetruecolor($w, $h);
        [$r1, $g1, $b1] = sscanf($from, '#%02x%02x%02x');
        [$r2, $g2, $b2] = sscanf($to, '#%02x%02x%02x');

        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            imageline($image, 0, $y, $w, $y, imagecolorallocate($image, (int) ($r1 + ($r2 - $r1) * $t), (int) ($g1 + ($g2 - $g1) * $t), (int) ($b1 + ($b2 - $b1) * $t)));
        }

        mt_srand(crc32($path));
        for ($i = 0; $i < 9; $i++) {
            $size = mt_rand(160, 520);
            imagefilledellipse($image, mt_rand(0, $w), mt_rand(0, $h), $size, $size, imagecolorallocatealpha($image, 255, 255, 255, mt_rand(88, 115)));
        }

        $this->save($image, $path);
    }

    protected function colour($image, string $hex): int
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return imagecolorallocate($image, $r, $g, $b);
    }

    protected function save($image, string $path): void
    {
        imagepng($image, public_path('assets/'.$path), 6);

        (Asset::find('assets::'.$path) ?? Asset::make()->container('assets')->path($path))->save();
    }
}
