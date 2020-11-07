<?php

namespace App\Http\Controllers\Dashboard;

use App\Config;
use App\Http\Controllers\Controller;
use Cache;
use Flash;
use Illuminate\Http\Request;
use Redirect;

class ApplicationController extends Controller
{
    /**
     * Get the edit form or update the terms of service and privacy policy.
     *
     * @param Request $request
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function tosAndPp(Request $request)
    {
        if ($request->isMethod('GET')) {
            return view('dashboard.tosPp');
        }

        $this->validate($request, [
            'terms_of_service' => 'string|max:3000',
            'privacy_policy' => 'string|max:3000',
        ]);

        $this->update($request->only(['terms_of_service', 'privacy_policy']));

        return Redirect::route('dashboard.tos-pp.index');
    }

    /**
     * Get the edit form or update the page info.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function page(Request $request)
    {
        if ($request->isMethod('GET')) {
            return view('dashboard.page');
        }

        $this->validate($request, [
            'page_name' => 'required|string|max:16',
            'extra_content' => 'string|max:255',
            'block_word_replacement' => 'string|max:1',
        ]);

        $this->update($request->only(['page_name', 'extra_content', 'block_word_replacement']));

        return Redirect::route('dashboard.page.index');
    }

    /**
     * Update config.
     *
     * @param array $input
     *
     * @return void
     */
    protected function update(array $input)
    {
        $key = 'application-service';

        Config::findOrFail($key)->update(['value' => array_merge(Config::getConfig($key), $input)]);

        Cache::forget($key);

        Flash::success('更新成功');
    }
}
