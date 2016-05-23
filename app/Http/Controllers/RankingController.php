<?php

namespace App\Http\Controllers;

use App\Config;
use App\Post;
use Carbon\Carbon;

class RankingController extends Controller
{
    /**
     * Get daily ranking posts.
     *
     * @return \Illuminate\View\View
     */
    public function daily()
    {
        return $this->posts(1);
    }

    /**
     * Get weekly ranking posts.
     *
     * @return \Illuminate\View\View
     */
    public function weekly()
    {
        return $this->posts(7);
    }

    /**
     * Get monthly ranking posts.
     *
     * @return \Illuminate\View\View
     */
    public function monthly()
    {
        return $this->posts(30);
    }

    /**
     * @param int $days
     *
     * @return \Illuminate\View\View
     */
    protected function posts($days)
    {
        $posts = Post::where('published_at', '>=', Carbon::now()->subDays($days))
            ->whereNotNull('fbid')
            ->orderBy('likes', 'desc')
            ->latest('published_at')
            ->paginate(5, ['id', 'fbid']);

        $pageId = Config::getConfig('facebook-service')['page_id'];

        return view('ranking', compact('days', 'posts', 'pageId'));
    }
}
