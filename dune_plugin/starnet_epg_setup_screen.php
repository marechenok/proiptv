<?php
/**
 * The MIT License (MIT)
 *
 * @Author: sharky72 (https://github.com/KocourKuba)
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

require_once 'lib/abstract_controls_screen.php';
require_once 'lib/user_input_handler.php';
require_once 'lib/epg_manager_sql.php';

///////////////////////////////////////////////////////////////////////////

class Starnet_Epg_Setup_Screen extends Abstract_Controls_Screen implements User_Input_Handler
{
    const ID = 'epg_setup';

    const CONTROL_CHANGE_CACHE_PATH = 'xmltv_cache_path';
    const CONTROL_ITEMS_CLEAR_EPG_CACHE = 'clear_epg_cache';

    ///////////////////////////////////////////////////////////////////////

    /**
     * EPG dialog defs
     * @return array
     */
    public function do_get_control_defs()
    {
        hd_debug_print(null, true);

        $defs = array();

        $this->plugin->init_playlist();

        //////////////////////////////////////
        // Plugin name
        $this->plugin->create_setup_header($defs);

        //////////////////////////////////////
        // XMLTV sources

        $sources = $this->plugin->get_all_xmltv_sources();
        Control_Factory::add_image_button($defs, $this, null, ACTION_ITEMS_EDIT,
            TR::t('setup_edit_xmltv_list'), TR::t('edit'), get_image_path('edit.png'), self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // EPG cache dir
        $cache_dir = $this->plugin->get_cache_dir();
        $free_size = TR::t('setup_storage_info__1', HD::get_storage_size($cache_dir));
        $cache_dir = HD::string_ellipsis($cache_dir . DIRECTORY_SEPARATOR);
        Control_Factory::add_image_button($defs, $this, null, self::CONTROL_CHANGE_CACHE_PATH,
            $free_size, $cache_dir, get_image_path('folder.png'), self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // EPG cache engine
        if (class_exists('SQLite3')) {
            $cache_engine[ENGINE_SQLITE] = TR::t('setup_epg_cache_sqlite');
            $cache_engine[ENGINE_LEGACY] = TR::t('setup_epg_cache_legacy');
            $engine = $this->plugin->get_parameter(PARAM_EPG_CACHE_ENGINE, ENGINE_SQLITE);

            Control_Factory::add_combobox($defs, $this, null,
                PARAM_EPG_CACHE_ENGINE, TR::t('setup_epg_cache_engine'),
                $engine, $cache_engine, self::CONTROLS_WIDTH, true);
        }

        //////////////////////////////////////
        // Fuzzy search
        $fuzzy_search = $this->plugin->get_parameter(PARAM_FUZZY_SEARCH_EPG, SetupControlSwitchDefs::switch_off);
        Control_Factory::add_image_button($defs, $this, null,
            PARAM_FUZZY_SEARCH_EPG, TR::t('entry_epg_fuzzy_search'), SetupControlSwitchDefs::$on_off_translated[$fuzzy_search],
            get_image_path(SetupControlSwitchDefs::$on_off_img[$fuzzy_search]), self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // Fake EPG
        $fake_epg = $this->plugin->get_parameter(PARAM_FAKE_EPG, SetupControlSwitchDefs::switch_off);
        Control_Factory::add_image_button($defs, $this, null,
            PARAM_FAKE_EPG, TR::t('entry_epg_fake'), SetupControlSwitchDefs::$on_off_translated[$fake_epg],
            get_image_path(SetupControlSwitchDefs::$on_off_img[$fake_epg]), self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // EPG cache
        $epg_cache_ops = array();
        $epg_cache_ops[0.5] = 0.5;
        $epg_cache_ops[1] = 1;
        $epg_cache_ops[2] = 2;
        $epg_cache_ops[3] = 3;
        $epg_cache_ops[4] = 4;
        $epg_cache_ops[5] = 5;
        $epg_cache_ops[6] = 6;
        $epg_cache_ops[7] = 7;

        $cache_ttl = $this->plugin->get_setting(PARAM_EPG_CACHE_TTL, 3);
        Control_Factory::add_combobox($defs, $this, null,
            PARAM_EPG_CACHE_TTL, TR::t('setup_epg_cache_ttl'),
            $cache_ttl, $epg_cache_ops, self::CONTROLS_WIDTH, true);

        //////////////////////////////////////
        // clear epg cache
        Control_Factory::add_image_button($defs, $this, null,
            self::CONTROL_ITEMS_CLEAR_EPG_CACHE, TR::t('entry_epg_cache_clear_all'), TR::t('clear'),
            get_image_path('brush.png'), self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // epg time shift
        /*
        $show_epg_shift_ops = array();
        for ($i = -11; $i < 12; $i++) {
            $show_epg_shift_ops[$i] = TR::t('setup_epg_shift__1', sprintf("%+03d", $i));
        }
        $show_epg_shift_ops[0] = TR::t('setup_epg_shift_default__1', "00");

        if (!isset($plugin_cookies->{PARAM_EPG_SHIFT})) {
            $plugin_cookies->{PARAM_EPG_SHIFT} = 0;
        }
        Control_Factory::add_combobox($defs, $this, null,
            PARAM_EPG_SHIFT, TR::t('setup_epg_shift'),
            $plugin_cookies->{PARAM_EPG_SHIFT}, $show_epg_shift_ops, self::CONTROLS_WIDTH);
        */
        return $defs;
    }

    /**
     * @inheritDoc
     */
    public function get_control_defs(MediaURL $media_url, &$plugin_cookies)
    {
        hd_debug_print(null, true);
        return $this->do_get_control_defs();
    }

    /**
     * @inheritDoc
     */
    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        hd_debug_print(null, true);
        dump_input_handler($user_input);

        $action_reload = User_Input_Handler_Registry::create_action($this, ACTION_RELOAD);
        $control_id = $user_input->control_id;
        if (isset($user_input->action_type, $user_input->{$control_id})
            && ($user_input->action_type === 'confirm' || $user_input->action_type === 'apply')) {
            $new_value = $user_input->{$control_id};
            hd_debug_print("Setup: changing $control_id value to $new_value");
        }

        switch ($control_id) {
            case ACTION_ITEMS_EDIT:
                $media_url_str = MediaURL::encode(
                    array(
                        'screen_id' => Starnet_Edit_List_Screen::ID,
                        'source_window_id' => static::ID,
                        'edit_list' => Starnet_Edit_List_Screen::SCREEN_EDIT_EPG_LIST,
                        'end_action' => ACTION_RELOAD,
                        'cancel_action' => RESET_CONTROLS_ACTION_ID,
                        'save_data' => PLUGIN_PARAMETERS,
                        'extension' => EPG_PATTERN,
                        'windowCounter' => 1,
                    )
                );
                return Action_Factory::open_folder($media_url_str, TR::t('setup_edit_xmltv_list'));

            case self::CONTROL_CHANGE_CACHE_PATH:
                $media_url_str = MediaURL::encode(
                    array(
                        'screen_id' => Starnet_Folder_Screen::ID,
                        'source_window_id' => static::ID,
                        'allow_network' => !is_apk(),
                        'choose_folder' => static::ID,
                        'allow_reset' => true,
                        'end_action' => ACTION_RELOAD,
                        'windowCounter' => 1,
                    )
                );
                return Action_Factory::open_folder($media_url_str, TR::t('setup_epg_xmltv_cache_caption'));

            case PARAM_EPG_CACHE_ENGINE:
                $this->plugin->tv->unload_channels();
                $this->plugin->set_parameter(PARAM_EPG_CACHE_ENGINE, $user_input->{$control_id});
                $this->plugin->init_plugin();
                return User_Input_Handler_Registry::create_action($this, ACTION_RELOAD);

            case PARAM_EPG_CACHE_TTL:
            case PARAM_EPG_SHIFT:
                $this->plugin->set_setting($control_id, $user_input->{$control_id});
                break;

            case self::CONTROL_ITEMS_CLEAR_EPG_CACHE:
                $this->plugin->tv->unload_channels();
                $this->plugin->get_epg_manager()->clear_all_epg_cache();
                return Action_Factory::show_title_dialog(TR::t('entry_epg_cache_cleared'),
                    Action_Factory::reset_controls($this->do_get_control_defs()));

            case ACTION_RESET_DEFAULT:
                hd_debug_print(ACTION_RESET_DEFAULT);
                $this->plugin->get_epg_manager()->clear_all_epg_cache();
                $this->plugin->remove_parameter(PARAM_CACHE_PATH);
                $this->plugin->init_epg_manager();

                $default_path = $this->plugin->get_cache_dir();
                return Action_Factory::show_title_dialog(TR::t('folder_screen_selected_folder__1', $default_path),
                    $action_reload, $default_path, self::CONTROLS_WIDTH);

            case ACTION_FOLDER_SELECTED:
                $data = MediaURL::decode($user_input->selected_data);
                hd_debug_print(ACTION_FOLDER_SELECTED . ": $data->filepath");
                if ($this->plugin->get_cache_dir() === $data->filepath) break;

                $this->plugin->get_epg_manager()->clear_all_epg_cache();
                $this->plugin->set_parameter(PARAM_CACHE_PATH, $data->filepath);
                $this->plugin->init_epg_manager();

                return Action_Factory::show_title_dialog(TR::t('folder_screen_selected_folder__1', $data->caption),
                    $action_reload, $data->filepath, self::CONTROLS_WIDTH);

            case PARAM_FUZZY_SEARCH_EPG:
            case PARAM_FAKE_EPG:
                $this->plugin->toggle_parameter($control_id, false);
                $this->plugin->init_epg_manager();
                break;

            case ACTION_RELOAD:
                hd_debug_print(ACTION_RELOAD);
                $this->plugin->tv->reload_channels($plugin_cookies);
                return Action_Factory::invalidate_all_folders($plugin_cookies,
                    Action_Factory::reset_controls($this->do_get_control_defs()));
        }

        return Action_Factory::reset_controls($this->do_get_control_defs());
    }
}
