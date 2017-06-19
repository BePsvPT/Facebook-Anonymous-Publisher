<?php

namespace App\Http\Controllers;

use App\Block;
use App\Config;
use App\Http\Requests\KobeRequest;
use App\Post;
use Cache;
use Carbon\Carbon;
use FacebookAnonymousPublisher\Firewall\Firewall;
use FacebookAnonymousPublisher\GraphApi\GraphApi;
use FacebookAnonymousPublisher\TextToImage\TextToImage;
use FacebookAnonymousPublisher\Wordfilter\Wordfilter;
use Illuminate\Http\Request;

class KobeController extends Controller
{
    /**
     * The application config.
     *
     * @var array
     */
    protected $application;

    /**
     * @var Post
     */
    protected $post;

    /**
     * @var GraphApi
     */
    protected $graphApi;

    /**
     * @var Firewall
     */
    protected $firewall;

    /**
     * @var Wordfilter
     */
    protected $wordfilter;

    /**
     * @var TextToImage
     */
    protected $textToImage;

    /**
     * Constructor.
     *
     * @param Firewall $firewall
     * @param Wordfilter $wordfilter
     * @param TextToImage $textToImage
     */
    public function __construct(Firewall $firewall, Wordfilter $wordfilter, TextToImage $textToImage)
    {
        $this->firewall = $firewall;

        $this->wordfilter = $wordfilter;

        $this->textToImage = $textToImage;
    }

    /**
     * Post kobe.
     *
     * @param KobeRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function kobe(KobeRequest $request)
    {
        $this->init();

        $this->save($request);

        $file = $request->has('post-by-image') ? $this->canvas($request->input('color')) : $request->file('image');

        $this->posted($this->postFeed($file));

        return redirect("https://www.facebook.com/{$this->post->getAttribute('fbid')}");
    }

    /**
     * Initialize the kobe.
     */
    protected function init()
    {
        $this->application = Config::getConfig('application-service');

        $this->graphApi = new GraphApi(Config::getConfig('facebook-service'));

        $this->post = new Post;
    }

    /**
     * Create post.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function save(Request $request)
    {
        $content = $this->filterBlockWords(
            $this->stripCharacters(
                $this->normalizeNewLine(
                    $request->input('content')
                )
            )
        );

        $this->post->setAttribute('user_id', ((false !== $this->firewall->isBanned()) && ! is_null($request->user())) ? $request->user()->getKey() : null);
        $this->post->setAttribute('content', $this->transformHashTag($content));
        $this->post->setAttribute('link', $request->has('nolink') ? null : $this->findLink($content));
        $this->post->setAttribute('has_image', $request->has('post-by-image') || $request->hasFile('image'));
        $this->post->setAttribute('user_agent', $request->header('user-agent'));
        $this->post->setAttribute('ip', $this->firewall->ip());
        $this->post->setAttribute('created_at', Carbon::now());
        $this->post->setAttribute('sync_at', Carbon::now());

        return $this->post->save();
    }

    /**
     * Normalize new line symbol.
     *
     * @param string $content
     *
     * @return string
     */
    protected function normalizeNewLine($content)
    {
        $content = str_replace(["\r\n", "\r", "\n"], $this->newLines(1), $content);

        while (str_contains($content, $this->newLines(3))) {
            $content = str_replace($this->newLines(3), $this->newLines(2), $content);
        }

        return $content;
    }

    /**
     * Strip special characters.
     *
     * @param string $string
     *
     * @return string
     */
    protected function stripCharacters($string)
    {
        $removes = [];

        foreach (preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY) as $char) {
            if ((1 === strlen($char)) && ($this->newLines(1) !== $char) && (! ctype_print($char) || ctype_cntrl($char))) {
                $removes[] = $char;
            }
        }

        return str_replace(array_merge(array_unique($removes, SORT_REGULAR), [d('&lrm;')]), '', $string);
    }

    /**
     * Filter the block words.
     *
     * @param string $content
     *
     * @return string
     */
    protected function filterBlockWords($content)
    {
        $words = $this->blockWords();

        if (empty($words)) {
            return $content;
        }

        return $this->wordfilter->replace(
            $words,
            $this->application['block_word_replacement'] ?? '',
            $content
        );
    }

    /**
     * Get block words.
     *
     * @return array
     */
    protected function blockWords()
    {
        return Cache::remember('block-words', 30, function () {
            return Block::where('type', 'keyword')
                ->orderByRaw('LENGTH(`value`) DESC')
                ->get(['value'])
                ->pluck('value')
                ->toArray();
        });
    }

    /**
     * Transform hash tag to more powerful.
     *
     * @param string $content
     *
     * @return string
     */
    protected function transformHashTag($content)
    {
        if (0 === preg_match_all('/#('.$this->application['page_name'].')?(\d+)/', $content, $matches)) {
            return $content;
        }

        $stack = [];

        foreach ($matches[2] as $index => $match) {
            $post = Post::find($match, ['fbid']);

            if (is_null($post) || in_array($post->getAttribute('fbid'), $stack)) {
                continue;
            }

            $content = $this->addLinkToHashTag($matches[0][$index], $post->getAttribute('fbid'), $content);

            $stack[] = $post->getAttribute('fbid');
        }

        return $content;
    }

    /**
     * Append link to hash tag.
     *
     * @param string $hashTag
     * @param int $fbid
     * @param string $content
     *
     * @return mixed
     */
    protected function addLinkToHashTag($hashTag, $fbid, $content)
    {
        return str_replace(
            $hashTag,
            "{$hashTag} (https://www.facebook.com/{$fbid})",
            $content
        );
    }

    /**
     * Find links in content.
     *
     * @param $content
     * @param bool $all
     *
     * @return null|string
     */
    protected function findLink($content, $all = false)
    {
        $amount = preg_match_all(
            '/\b(https?):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;\p{Han}]*[-A-Z0-9+&@#\/%=~_|\p{Han}]/ui',
            $content,
            $matches
        );

        if (0 === $amount) {
            return null;
        }

        return $all ? $matches[0] : $matches[0][0];
    }

    /**
     * Create an image using the post content and return the image path.
     *
     * @param string $color
     *
     * @return array
     */
    protected function canvas($color)
    {
        $filePath = [];

        $images = $this->textToImage
            ->setColor($color)
            ->make($this->post->getAttribute('content'), true);

        foreach ($images as $index => $image) {
            $path = sprintf('%s/%d-%d.jpg', $this->imageDirectory(), $this->post->getKey(), $index);

            $image->save($path, 100);

            $filePath[] = $path;
        }

        return $filePath;
    }

    /**
     * Post to feed.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile|array|null $file
     *
     * @return GraphApi
     */
    protected function postFeed($file)
    {
        if (! is_null($file)) {
            return $this->postPhotos($file);
        }

        return $this->graphApi->status(
            $this->content(),
            $this->post->getAttribute('link')
        );
    }

    /**
     * Post a photo.
     *
     * @param array|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return GraphApi
     */
    protected function postPhotos($file)
    {
        if (is_array($file)) {
            return $this->graphApi->photos($file, $this->content(true));
        }

        $source = $file->move($this->imageDirectory(), $this->post->getKey().'.'.$file->guessExtension())->getPathname();

        return $this->graphApi->photo($source, $this->content());
    }

    /**
     * Get the directory that store images.
     *
     * @return string
     */
    protected function imageDirectory()
    {
        $path = storage_path('app/images/'.intval(floor($this->post->getKey() / 5000)));

        if (! is_dir($path)) {
            mkdir($path);
        }

        return $path;
    }

    /**
     * Get post content.
     *
     * @param bool $textImage
     *
     * @return string
     */
    protected function content($textImage = false)
    {
        return implode('', [
            // Page hash tag
            '#'.$this->application['page_name'].$this->post->getKey(),
            $this->newLines(1),

            // Extra content that should insert to the post
            $this->application['extra_content'] ?? '',
            $this->newLines(2),

            // User post content
            $textImage ? $this->getTextImageLinks() : $this->post->getAttribute('content'),
            $this->newLines(2),

            // Post submitted time
            'Submitted At: '.$this->post->getAttribute('created_at'),
        ]);
    }

    /**
     * Get all links from text image.
     *
     * @return string
     */
    protected function getTextImageLinks()
    {
        $links = $this->findLink($this->post->getAttribute('content'), true);

        if (is_null($links)) {
            return '';
        }

        return implode($this->newLines(1), $links);
    }

    /**
     * Save fbid and published_at.
     *
     * @param GraphApi $api
     *
     * @return bool
     */
    protected function posted(GraphApi $api)
    {
        $this->post->setAttribute('fbid', $api->getId()['fbid']);
        $this->post->setAttribute('published_at', Carbon::now());

        return $this->post->save();
    }

    /**
     * Get specific amount of new lines.
     *
     * @param int $multiplier
     *
     * @return string
     */
    protected function newLines($multiplier = 1)
    {
        return str_repeat(PHP_EOL, $multiplier);
    }
}
