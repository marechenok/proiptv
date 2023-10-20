<?php
/**
 * The MIT License (MIT)
 *
 * @Author: sharky72 (https://github.com/KocourKuba)
 * Original code from DUNE HD
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense
 * of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

require_once 'abstract_regular_screen.php';

abstract class Abstract_Preloaded_Regular_Screen extends Abstract_Regular_Screen
{
    const DLG_CONTROLS_WIDTH = 850;

    /**
     * @var bool
     */
    private $has_changes = false;

    /**
     * @param $value
     * @return bool
     */
    protected function set_changes($value = true)
    {
        if ($value) {
            $this->plugin->set_durty();
        }

        $old = $this->has_changes;
        $this->has_changes = $value;
        return $old;
    }

    protected function has_changes()
    {
        return $this->has_changes;
    }

    /**
     * @param MediaURL $media_url
     * @param $plugin_cookies
     * @return array
     */
    abstract public function get_all_folder_items(MediaURL $media_url, &$plugin_cookies);

    /**
     * @inheritDoc
     */
    public function get_folder_range(MediaURL $media_url, $from_ndx, &$plugin_cookies)
    {
        hd_debug_print(null, true);
        hd_debug_print("from_ndx: $from_ndx, MediaURL: $media_url", true);

        $items = $this->get_all_folder_items($media_url, $plugin_cookies);
        $count = count($items);
        $total = $from_ndx + $count;

        if ($from_ndx >= $total) {
            $from_ndx = $total;
            $items = array();
        } else if ($from_ndx + $count > $total) {
            array_splice($items, $total - $from_ndx);
        }

        return array
        (
            PluginRegularFolderRange::total => $total,
            PluginRegularFolderRange::more_items_available => false,
            PluginRegularFolderRange::from_ndx => (int)$from_ndx,
            PluginRegularFolderRange::count => count($items),
            PluginRegularFolderRange::items => $items
        );
    }

    /**
     * @param MediaURL $parent_media_url
     * @param $plugin_cookies
     * @param int $sel_ndx
     * @return array
     */
    public function invalidate_current_folder(MediaURL $parent_media_url, $plugin_cookies, $sel_ndx = -1)
    {
        hd_debug_print(null, true);

        return Starnet_Epfs_Handler::invalidate_folders(array(static::ID),
            Action_Factory::update_regular_folder(
            $this->get_folder_range($parent_media_url, 0, $plugin_cookies),
            true,
            $sel_ndx)
        );
    }

    /**
     * @param $plugin_cookies
     * @param bool $all_except
     * @param array|null $media_urls
     * @param null $post_action
     * @return array
     */
    public function invalidate_epfs_folders($plugin_cookies, $media_urls = null, $post_action = null, $all_except = false)
    {
        hd_debug_print(null, true);

        $this->plugin->save_settings();
        if ($this->has_changes) {
            $this->set_changes(false);
            Starnet_Epfs_Handler::update_all_epfs($plugin_cookies);
        }

        return Starnet_Epfs_Handler::invalidate_folders($media_urls, $post_action, $all_except);
    }

    /**
     * @param $plugin_cookies
     * @param array|null $action
     * @param array|null $media_urls
     * @param array|null $post_action
     * @return array
     */
    public function update_invalidate_epfs_folders($plugin_cookies, $action, $media_urls = null, $post_action = null)
    {
        hd_debug_print(null, true);

        $this->plugin->save_settings();
        if ($this->has_changes) {
            $this->set_changes(false);
            Starnet_Epfs_Handler::update_all_epfs($plugin_cookies);
        }

        $action = Action_Factory::update_invalidate_folders(
            Action_Factory::update_invalidate_folders($action, Starnet_Tv_Rows_Screen::ID),
            $media_urls,
            $post_action
        );

        hd_debug_print(raw_json_encode($action));
        return $action;
    }
}
