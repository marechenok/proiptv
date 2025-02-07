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

///////////////////////////////////////////////////////////////////////////

class Starnet_Setup_Screen extends Abstract_Controls_Screen implements User_Input_Handler
{
    const ID = 'setup';

    const ACTION_DONATE_DLG = 'donate_dlg';
    const CONTROL_INTERFACE_SCREEN = 'interface_screen';
    const CONTROL_PLAYLISTS_SCREEN = 'playlists_screen';
    const CONTROL_EPG_SCREEN = 'epg_screen';
    const CONTROL_STREAMING_SCREEN = 'streaming_screen';
    const CONTROL_EXT_SETUP_SCREEN = 'extended_setup_screen';

    ///////////////////////////////////////////////////////////////////////

    /**
     * @inheritDoc
     */
    public function get_action_map(MediaURL $media_url, &$plugin_cookies)
    {
        hd_debug_print(null, true);
        $actions[GUI_EVENT_KEY_RETURN] = User_Input_Handler_Registry::create_action($this, GUI_EVENT_KEY_RETURN);
        return $actions;
    }

    /**
     * defs for all controls on screen
     * @return array
     */
    public function do_get_control_defs()
    {
        hd_debug_print(null, true);

        $setting_icon = get_image_path('settings.png');

        $defs = array();

        //////////////////////////////////////
        // Plugin name
        Control_Factory::add_vgap($defs, -10);
        Control_Factory::add_image_button($defs, $this, null,
            ACTION_PLUGIN_INFO,
            Default_Dune_Plugin::AUTHOR_LOGO,
            " v.{$this->plugin->plugin_info['app_version']} [{$this->plugin->plugin_info['app_release_date']}]",
            get_image_path('info.png'),
            Abstract_Controls_Screen::CONTROLS_WIDTH);

        Control_Factory::add_vgap($defs, 16);

        Control_Factory::add_button($defs, $this,null, self::ACTION_DONATE_DLG,
            TR::t('setup_donate_title'), 'QR code', self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // Interface settings 3
        Control_Factory::add_image_button($defs, $this, null, self::CONTROL_INTERFACE_SCREEN,
            TR::t('setup_interface_title'), TR::t('setup_change_settings'), $setting_icon, self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // Channels settings 5
        Control_Factory::add_image_button($defs, $this, null, self::CONTROL_PLAYLISTS_SCREEN,
            TR::t('tv_screen_playlists_setup'), TR::t('setup_change_settings'), $setting_icon, self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // EPG settings 7
        Control_Factory::add_image_button($defs, $this, null, self::CONTROL_EPG_SCREEN,
            TR::t('setup_epg_settings'), TR::t('setup_change_settings'), $setting_icon, self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // Streaming settings 9
        Control_Factory::add_image_button($defs, $this, null, self::CONTROL_STREAMING_SCREEN,
            TR::t('setup_streaming_settings'), TR::t('setup_change_settings'), $setting_icon, self::CONTROLS_WIDTH);

        //////////////////////////////////////
        // Extended settings 11
        Control_Factory::add_image_button($defs, $this, null,
            self::CONTROL_EXT_SETUP_SCREEN,
            TR::t('setup_extended_setup'), TR::t('setup_change_settings'), $setting_icon, self::CONTROLS_WIDTH);

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

    public function do_donate_dialog()
    {
        try {
            hd_debug_print(null, true);
            $img_ym = get_temp_path('qr_ym.png');
            file_put_contents($img_ym, HD::http_get_document(Default_Dune_Plugin::RESOURCE_URL . "QR_YM.png"));
            $img_pp = get_temp_path('qr_pp.png');
            file_put_contents($img_pp, HD::http_get_document(Default_Dune_Plugin::RESOURCE_URL . "QR_PP.png"));

            Control_Factory::add_vgap($defs, 50);
            Control_Factory::add_smart_label($defs, "", "<text>YooMoney</text><gap width=400/><text>PayPal</text>");
            Control_Factory::add_smart_label($defs, "", "<icon>$img_ym</icon><gap width=140/><icon>$img_pp</icon>");
            Control_Factory::add_vgap($defs, 450);

            $attrs['dialog_params'] = array('frame_style' => DIALOG_FRAME_STYLE_GLASS);
            return Action_Factory::show_dialog("QR code", $defs, true, 1150, $attrs);
        } catch (Exception $ex) {
        }

        return Action_Factory::status(0);
    }

    /**
     * @inheritDoc
     */
    public function handle_user_input(&$user_input, &$plugin_cookies)
    {
        hd_debug_print(null, true);
        dump_input_handler($user_input);

        static $history_txt;

        if (empty($history_txt)) {
            $history_txt = file_get_contents(get_install_path('changelog.md'));
            $history_txt = preg_replace('/\n$/U', '', $history_txt);
        }

        $control_id = $user_input->control_id;
        switch ($control_id) {
            case GUI_EVENT_KEY_RETURN:
                return Action_Factory::close_and_run(
                    Action_Factory::close_and_run(
                        Action_Factory::open_folder(Starnet_Tv_Groups_Screen::ID, $this->plugin->create_plugin_title())
                    )
                );

            case ACTION_PLUGIN_INFO:
                Control_Factory::add_multiline_label($defs, null, $history_txt, 12);
                Control_Factory::add_vgap($defs, 20);

                $text = sprintf("<gap width=%s/><icon>%s</icon><gap width=10/><icon>%s</icon><text color=%s size=small>  %s</text>",
                    1160,
                    get_image_path('page_plus_btn.png'),
                    get_image_path('page_minus_btn.png'),
                    DEF_LABEL_TEXT_COLOR_SILVER,
                    TR::load_string('scroll_page')
                );
                Control_Factory::add_smart_label($defs, '', $text);
                Control_Factory::add_vgap($defs, -80);

                Control_Factory::add_close_dialog_button($defs, TR::t('ok'), 250, true);
                Control_Factory::add_vgap($defs, 10);

                return Action_Factory::show_dialog(TR::t('setup_changelog'), $defs, true, 1600);

            case self::ACTION_DONATE_DLG: // show donate QR codes
                return $this->do_donate_dialog();

            case self::CONTROL_INTERFACE_SCREEN: // show interface settings dialog
                return Action_Factory::open_folder(Starnet_Interface_Setup_Screen::get_media_url_str(), TR::t('setup_interface_title'));

            case self::CONTROL_PLAYLISTS_SCREEN: // show epg settings dialog
                return Action_Factory::open_folder(Starnet_Playlists_Setup_Screen::get_media_url_str(), TR::t('tv_screen_playlists_setup'));

            case self::CONTROL_EPG_SCREEN: // show epg settings dialog
                return Action_Factory::open_folder(Starnet_Epg_Setup_Screen::get_media_url_str(), TR::t('setup_epg_settings'));

            case self::CONTROL_STREAMING_SCREEN: // show streaming settings dialog
                return Action_Factory::open_folder(Starnet_Streaming_Setup_Screen::get_media_url_str(), TR::t('setup_streaming_settings'));

            case self::CONTROL_EXT_SETUP_SCREEN: // show additional settings dialog
                return Action_Factory::open_folder(Starnet_Ext_Setup_Screen::get_media_url_str(), TR::t('setup_extended_setup'));

            case RESET_CONTROLS_ACTION_ID:
                $sel_ndx = isset($user_input->initial_sel_ndx) ? $user_input->initial_sel_ndx : -1;
                return Action_Factory::reset_controls($this->do_get_control_defs(), null, $sel_ndx);
        }

        return null;
    }
}
