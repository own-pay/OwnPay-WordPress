<?php
/**
 * Register administrative menus for payment oversight
 *
 * @package     OPWC
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('OPWC_Menu_Settings')) {

    class OPWC_Menu_Settings
    {
        private $plugin_name;
        private $version;
        private $page_hook;
        public $class_prefix = 'class-opwc-';

        public function __construct($plugin_name, $version)
        {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
        }

        /**
         * Register hooks. Called externally after construction.
         */
        public function init()
        {
            add_action('admin_menu', [$this, 'register_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_page_scripts']);
        }

        public function get_submenu_list()
        {
            $submenu_list = [];
            return $submenu_list;
        }

        public function register_admin_menu()
        {
            $main_menu_title = 'OwnPay';
            $parent_slug = 'opwc';
            $capability = 'manage_options';

            // Use the SVG URL directly — no filesystem read required.
            $icon_path = plugin_dir_path(__FILE__) . '../assets/logo/icon.svg';
            // Use a base64 data URI so the icon renders reliably in the WordPress admin sidebar.
            // Direct SVG file URLs can fail due to extra XML namespaces (Inkscape, Sodipodi).
            $menu_icon_url = file_exists($icon_path)
                ? 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjwhLS0gQ3JlYXRlZCB3aXRoIElua3NjYXBlIChodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy8pIC0tPgoKPHN2ZwogICB2ZXJzaW9uPSIxLjEiCiAgIGlkPSJzdmcxIgogICB3aWR0aD0iMjY2Ni42NjY3IgogICBoZWlnaHQ9IjI2NjYuNjY2NyIKICAgdmlld0JveD0iMCAwIDI2NjYuNjY2NyAyNjY2LjY2NjciCiAgIHNvZGlwb2RpOmRvY25hbWU9ImxvZ28gQUkuYWkiCiAgIHhtbG5zOmlua3NjYXBlPSJodHRwOi8vd3d3Lmlua3NjYXBlLm9yZy9uYW1lc3BhY2VzL2lua3NjYXBlIgogICB4bWxuczpzb2RpcG9kaT0iaHR0cDovL3NvZGlwb2RpLnNvdXJjZWZvcmdlLm5ldC9EVEQvc29kaXBvZGktMC5kdGQiCiAgIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIgogICB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciCiAgIHhtbG5zOnN2Zz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPgogIDxkZWZzCiAgICAgaWQ9ImRlZnMxIj4KICAgIDxjb2xvci1wcm9maWxlCiAgICAgICBpbmtzY2FwZTpsYWJlbD0ic1JHQiBJRUM2MTk2Ni0yLjEiCiAgICAgICBuYW1lPSJzUkdCIElFQzYxOTY2LTIuMSIKICAgICAgIHhsaW5rOmhyZWY9ImRhdGE6YXBwbGljYXRpb24vdm5kLmljY3Byb2ZpbGU7YmFzZTY0LEFBQU1iR3hqYlhNQ0VBQUFiVzUwY2xKSFFpQllXVm9nQjg0QUFnQUpBQVlBTVFBQVlXTnpjRUZRVUV3QUFBQUFTVVZESUhOU1IwSUFBQUFBQUFBQUFBQUFBQUFBQVBiV0FBRUFBQUFBMHkxc1kyMXpBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFSWTNCeWRBQUFBVkFBQUFBelpHVnpZd0FBQVlRQUFBQ1FkM1J3ZEFBQUFoUUFBQUFVWW10d2RBQUFBaWdBQUFBVWNsaFpXZ0FBQWp3QUFBQVVaMWhaV2dBQUFsQUFBQUFVWWxoWldnQUFBbVFBQUFBVVpHMXVaQUFBQW5nQUFBQndaRzFrWkFBQUF1Z0FBQUNJZG5WbFpBQUFBM0FBQUFDR2RtbGxkd0FBQS9nQUFBQWtiSFZ0YVFBQUJCd0FBQUFVYldWaGN3QUFCREFBQUFBa2RHVmphQUFBQkZRQUFBQU1jbFJTUXdBQUJHQUFBQWdNWjFSU1F3QUFCR0FBQUFnTVlsUlNRd0FBQkdBQUFBZ01kR1Y0ZEFBQUFBQkRiM0I1Y21sbmFIUWdLR01wSURFNU9UZ2dTR1YzYkdWMGRDMVFZV05yWVhKa0lFTnZiWEJoYm5rQUFHUmxjMk1BQUFBQUFBQUFFbk5TUjBJZ1NVVkROakU1TmpZdE1pNHhBQUFBQUFBQUFBQVNBSE1BVWdCSEFFSUFJQUJKQUVVQVF3QTJBREVBT1FBMkFEWUFMUUF5QUM0QU1RQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFGaFpXaUFBQUFBQUFBRHpVUUFCQUFBQUFSYk1XRmxhSUFBQUFBQUFBQUFBQUFBQUFBQUFBQUJZV1ZvZ0FBQUFBQUFBYjZJQUFEajFBQUFEa0ZoWldpQUFBQUFBQUFCaW1RQUF0NFVBQUJqYVdGbGFJQUFBQUFBQUFDU2dBQUFQaEFBQXRzOWtaWE5qQUFBQUFBQUFBQlpKUlVNZ2FIUjBjRG92TDNkM2R5NXBaV011WTJnQUFBQUFBQUFBQUFBQUFCWkpSVU1nYUhSMGNEb3ZMM2QzZHk1cFpXTXVZMmdBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBWkdWell3QUFBQUFBQUFBdVNVVkRJRFl4T1RZMkxUSXVNU0JFWldaaGRXeDBJRkpIUWlCamIyeHZkWElnYzNCaFkyVWdMU0J6VWtkQ0FBQUFBQUFBQUFBQUFBQXVTVVZESURZeE9UWTJMVEl1TVNCRVpXWmhkV3gwSUZKSFFpQmpiMnh2ZFhJZ2MzQmhZMlVnTFNCelVrZENBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUdSbGMyTUFBQUFBQUFBQUxGSmxabVZ5Wlc1alpTQldhV1YzYVc1bklFTnZibVJwZEdsdmJpQnBiaUJKUlVNMk1UazJOaTB5TGpFQUFBQUFBQUFBQUFBQUFDeFNaV1psY21WdVkyVWdWbWxsZDJsdVp5QkRiMjVrYVhScGIyNGdhVzRnU1VWRE5qRTVOall0TWk0eEFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFCMmFXVjNBQUFBQUFBVHBQNEFGRjh1QUJEUEZBQUQ3Y3dBQkJNTEFBTmNuZ0FBQUFGWVdWb2dBQUFBQUFCTUNWWUFVQUFBQUZjZjUyMWxZWE1BQUFBQUFBQUFBUUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUtQQUFBQUFuTnBaeUFBQUFBQVExSlVJR04xY25ZQUFBQUFBQUFFQUFBQUFBVUFDZ0FQQUJRQUdRQWVBQ01BS0FBdEFESUFOd0E3QUVBQVJRQktBRThBVkFCWkFGNEFZd0JvQUcwQWNnQjNBSHdBZ1FDR0FJc0FrQUNWQUpvQW53Q2tBS2tBcmdDeUFMY0F2QURCQU1ZQXl3RFFBTlVBMndEZ0FPVUE2d0R3QVBZQSt3RUJBUWNCRFFFVEFSa0JId0VsQVNzQk1nRTRBVDRCUlFGTUFWSUJXUUZnQVdjQmJnRjFBWHdCZ3dHTEFaSUJtZ0doQWFrQnNRRzVBY0VCeVFIUkFka0I0UUhwQWZJQitnSURBZ3dDRkFJZEFpWUNMd0k0QWtFQ1N3SlVBbDBDWndKeEFub0NoQUtPQXBnQ29nS3NBcllDd1FMTEF0VUM0QUxyQXZVREFBTUxBeFlESVFNdEF6Z0RRd05QQTFvRFpnTnlBMzREaWdPV0E2SURyZ082QThjRDB3UGdBK3dEK1FRR0JCTUVJQVF0QkRzRVNBUlZCR01FY1FSK0JJd0VtZ1NvQkxZRXhBVFRCT0VFOEFUK0JRMEZIQVVyQlRvRlNRVllCV2NGZHdXR0JaWUZwZ1cxQmNVRjFRWGxCZllHQmdZV0JpY0dOd1pJQmxrR2FnWjdCb3dHblFhdkJzQUcwUWJqQnZVSEJ3Y1pCeXNIUFFkUEIyRUhkQWVHQjVrSHJBZS9COUlINVFmNENBc0lId2d5Q0VZSVdnaHVDSUlJbGdpcUNMNEkwZ2puQ1BzSkVBa2xDVG9KVHdsa0NYa0pqd21rQ2JvSnp3bmxDZnNLRVFvbkNqMEtWQXBxQ29FS21BcXVDc1VLM0FyekN3c0xJZ3M1QzFFTGFRdUFDNWdMc0F2SUMrRUwrUXdTRENvTVF3eGNESFVNamd5bkRNQU0yUXp6RFEwTkpnMUFEVm9OZEEyT0Rha053dzNlRGZnT0V3NHVEa2tPWkE1L0Rwc090ZzdTRHU0UENROGxEMEVQWGc5NkQ1WVBzdy9QRCt3UUNSQW1FRU1RWVJCK0VKc1F1UkRYRVBVUkV4RXhFVThSYlJHTUVhb1J5UkhvRWdjU0poSkZFbVFTaEJLakVzTVM0eE1ERXlNVFF4TmpFNE1UcEJQRkUrVVVCaFFuRkVrVWFoU0xGSzBVemhUd0ZSSVZOQlZXRlhnVm14VzlGZUFXQXhZbUZra1diQmFQRnJJVzFoYjZGeDBYUVJkbEY0a1hyaGZTRi9jWUd4aEFHR1VZaWhpdkdOVVkraGtnR1VVWmF4bVJHYmNaM1JvRUdpb2FVUnAzR3A0YXhScnNHeFFiT3h0akc0b2JzaHZhSEFJY0toeFNISHNjb3h6TUhQVWRIaDFISFhBZG1SM0RIZXdlRmg1QUhtb2VsQjYrSHVrZkV4OCtIMmtmbEIrL0grb2dGU0JCSUd3Z21DREVJUEFoSENGSUlYVWhvU0hPSWZzaUp5SlZJb0lpcnlMZEl3b2pPQ05tSTVRandpUHdKQjhrVFNSOEpLc2syaVVKSlRnbGFDV1hKY2NsOXlZbkpsY21oeWEzSnVnbkdDZEpKM29ucXlmY0tBMG9QeWh4S0tJbzFDa0dLVGdwYXltZEtkQXFBaW8xS21ncW15clBLd0lyTml0cEs1MHIwU3dGTERrc2JpeWlMTmN0REMxQkxYWXRxeTNoTGhZdVRDNkNMcmN1N2k4a0wxb3ZrUy9ITC80d05UQnNNS1F3MnpFU01Vb3hnakc2TWZJeUtqSmpNcHN5MURNTk0wWXpmek80TS9FMEt6UmxOSjQwMkRVVE5VMDFoelhDTmYwMk56WnlOcTQyNlRja04yQTNuRGZYT0JRNFVEaU1PTWc1QlRsQ09YODV2RG41T2pZNmREcXlPdTg3TFR0ck82bzc2RHduUEdVOHBEempQU0k5WVQyaFBlQStJRDVnUHFBKzREOGhQMkUvb2ovaVFDTkFaRUNtUU9kQktVRnFRYXhCN2tJd1FuSkN0VUwzUXpwRGZVUEFSQU5FUjBTS1JNNUZFa1ZWUlpwRjNrWWlSbWRHcTBid1J6VkhlMGZBU0FWSVMwaVJTTmRKSFVsalNhbEo4RW8zU24xS3hFc01TMU5MbWt2aVRDcE1ja3k2VFFKTlNrMlRUZHhPSlU1dVRyZFBBRTlKVDVOUDNWQW5VSEZRdTFFR1VWQlJtMUhtVWpGU2ZGTEhVeE5UWDFPcVUvWlVRbFNQVk50VktGVjFWY0pXRDFaY1ZxbFc5MWRFVjVKWDRGZ3ZXSDFZeTFrYVdXbFp1Rm9IV2xaYXBscjFXMFZibFZ2bFhEVmNobHpXWFNkZGVGM0pYaHBlYkY2OVh3OWZZVit6WUFWZ1YyQ3FZUHhoVDJHaVlmVmlTV0tjWXZCalEyT1hZK3RrUUdTVVpPbGxQV1dTWmVkbVBXYVNadWhuUFdlVForbG9QMmlXYU94cFEybWFhZkZxU0dxZmF2ZHJUMnVuYS85c1YyeXZiUWh0WUcyNWJoSnVhMjdFYng1dmVHL1JjQ3R3aG5EZ2NUcHhsWEh3Y2t0eXBuTUJjMTF6dUhRVWRIQjB6SFVvZFlWMTRYWStkcHQyK0hkV2Q3TjRFWGh1ZU14NUtubUplZWQ2Um5xbGV3UjdZM3ZDZkNGOGdYemhmVUY5b1g0QmZtSit3bjhqZjRSLzVZQkhnS2lCQ29GcmdjMkNNSUtTZ3ZTRFY0TzZoQjJFZ0lUamhVZUZxNFlPaG5LRzE0YzdoNStJQklocGlNNkpNNG1aaWY2S1pJcktpekNMbG92OGpHT015bzB4alppTi80NW1qczZQTm8rZWtBYVFicERXa1QrUnFKSVJrbnFTNDVOTms3YVVJSlNLbFBTVlg1WEpsalNXbjVjS2wzV1g0SmhNbUxpWkpKbVFtZnlhYUpyVm0wS2JyNXdjbkltYzk1MWtuZEtlUUo2dW54MmZpNS82b0dtZzJLRkhvYmFpSnFLV293YWpkcVBtcEZha3g2VTRwYW1tR3FhTHB2Mm5icWZncUZLb3hLazNxYW1xSEtxUHF3S3JkYXZwckZ5czBLMUVyYml1TGE2aHJ4YXZpN0FBc0hXdzZyRmdzZGF5UzdMQ3N6aXpyclFsdEp5MUU3V0t0Z0cyZWJid3QyaTM0TGhadU5HNVNybkN1anU2dGJzdXU2ZThJYnlidlJXOWo3NEt2b1MrLzc5NnYvWEFjTURzd1dmQjQ4SmZ3dHZEV01QVXhGSEV6c1ZMeGNqR1JzYkR4MEhIdjhnOXlMekpPc201eWpqS3Q4czJ5N2JNTmN5MXpUWE50YzQyenJiUE44KzQwRG5RdXRFODBiN1NQOUxCMDBUVHh0UkoxTXZWVHRYUjFsWFcyTmRjMStEWVpOam8yV3paOGRwMjJ2dmJnTndGM0lyZEVOMlczaHplb3Q4cDM2L2dOdUM5NFVUaHpPSlQ0dHZqWStQcjVIUGsvT1dFNWczbWx1Y2Y1Nm5vTXVpODZVYnAwT3BiNnVYcmNPdjc3SWJ0RWUyYzdpanV0TzlBNzh6d1dQRGw4WEx4Ly9LTTh4bnpwL1EwOU1MMVVQWGU5bTMyKy9lSytCbjRxUGs0K2NmNlYvcm4rM2Y4Qi95WS9Tbjl1djVML3R6L2JmLy8iCiAgICAgICBpZD0iY29sb3ItcHJvZmlsZTEiIC8+CiAgICA8Y2xpcFBhdGgKICAgICAgIGNsaXBQYXRoVW5pdHM9InVzZXJTcGFjZU9uVXNlIgogICAgICAgaWQ9ImNsaXBQYXRoNzgiPgogICAgICA8cGF0aAogICAgICAgICBkPSJNIDAsMjAwMCBIIDIwMDAgViAwIEggMCBaIgogICAgICAgICB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMTQyMy4yMTAzLC0xNjYyLjM5NzUpIgogICAgICAgICBpZD0icGF0aDc4IiAvPgogICAgPC9jbGlwUGF0aD4KICAgIDxjbGlwUGF0aAogICAgICAgY2xpcFBhdGhVbml0cz0idXNlclNwYWNlT25Vc2UiCiAgICAgICBpZD0iY2xpcFBhdGg4MCI+CiAgICAgIDxwYXRoCiAgICAgICAgIGQ9Ik0gMCwyMDAwIEggMjAwMCBWIDAgSCAwIFoiCiAgICAgICAgIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0xNjQwLjA1NDksLTE0MjYuODQzOCkiCiAgICAgICAgIGlkPSJwYXRoODAiIC8+CiAgICA8L2NsaXBQYXRoPgogIDwvZGVmcz4KICA8c29kaXBvZGk6bmFtZWR2aWV3CiAgICAgaWQ9Im5hbWVkdmlldzEiCiAgICAgcGFnZWNvbG9yPSIjZmZmZmZmIgogICAgIGJvcmRlcmNvbG9yPSIjMDAwMDAwIgogICAgIGJvcmRlcm9wYWNpdHk9IjAuMjUiCiAgICAgaW5rc2NhcGU6c2hvd3BhZ2VzaGFkb3c9IjIiCiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAuMCIKICAgICBpbmtzY2FwZTpwYWdlY2hlY2tlcmJvYXJkPSIwIgogICAgIGlua3NjYXBlOmRlc2tjb2xvcj0iI2QxZDFkMSI+CiAgICA8aW5rc2NhcGU6cGFnZQogICAgICAgeD0iMCIKICAgICAgIHk9IjAiCiAgICAgICBpbmtzY2FwZTpsYWJlbD0iMiIKICAgICAgIGlkPSJwYWdlNzYiCiAgICAgICB3aWR0aD0iMjY2Ni42NjY3IgogICAgICAgaGVpZ2h0PSIyNjY2LjY2NjciCiAgICAgICBtYXJnaW49IjMzNC4zNzMzMiAyOTQuNDEzMzMgMzM0LjM3MzMyIDI5NC40MTE5OSIKICAgICAgIGJsZWVkPSIwIiAvPgogIDwvc29kaXBvZGk6bmFtZWR2aWV3PgogIDxnCiAgICAgaWQ9ImxheWVyLU1DMCIKICAgICBpbmtzY2FwZTpncm91cG1vZGU9ImxheWVyIgogICAgIGlua3NjYXBlOmxhYmVsPSJMYXllciAxIgogICAgIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0yNjg2LjY2NjgpIj4KICAgIDxwYXRoCiAgICAgICBpZD0icGF0aDc3IgogICAgICAgZD0ibSAwLDAgLTE5NS42MzUsLTIyNi4yMDIgYyA3Mi45NTksLTMzLjg2OCAxMzYuMjcyLC04My41MzIgMTg0Ljc5OSwtMTQ0LjIzMyBsIDIyNi4xLDMwNC43NTQgeiIKICAgICAgIHN0eWxlPSJmaWxsOiMwZjk2ZWQ7ZmlsbC1vcGFjaXR5OjE7ZmlsbC1ydWxlOm5vbnplcm87c3Ryb2tlOm5vbmUiCiAgICAgICB0cmFuc2Zvcm09Im1hdHJpeCgxLjMzMzMzMzMsMCwwLC0xLjMzMzMzMzMsNDU4NC4yODAzLDQ1MC4xMzY2NykiCiAgICAgICBjbGlwLXBhdGg9InVybCgjY2xpcFBhdGg3OCkiIC8+CiAgICA8cGF0aAogICAgICAgaWQ9InBhdGg3OSIKICAgICAgIGQ9Im0gMCwwIC0xNzcuNDY5LC0yMTEuNDIxIGMgMzQuMjQ3LC02NC45NTUgNTMuNDk4LC0xMzguMTI1IDUzLjQ5OCwtMjE1LjQyMyAwLC0yNjguMDM2IC0yMzEuNTE0LC00ODYuMTEyIC01MTYuMDYzLC00ODYuMTEyIC0yODQuNTkxLDAgLTUxNi4xMDUsMjE4LjA3NiAtNTE2LjEwNSw0ODYuMTEyIDAsMjY4LjAzNiAyMzEuNTE0LDQ4Ni4xMTMgNTE2LjEwNSw0ODYuMTEzIDQyLjU0NiwwIDgzLjkxMSwtNC44ODcgMTIzLjQ2NiwtMTQuMTEyIEwgLTMyMy43MjQsMjU3LjggYyAtOTYuNzE3LDQxLjQ5MiAtMjAzLjc1NCw2NC41NzYgLTMxNi4zMSw2NC41NzYgLTQyOS42NjYsMCAtNzc5LjIxMiwtMzM2LjEwOCAtNzc5LjIxMiwtNzQ5LjIyIDAsLTQxMy4xMTEgMzQ5LjU0NiwtNzQ5LjIxOSA3NzkuMjEyLC03NDkuMjE5IDQyOS42MjQsMCA3NzkuMTcsMzM2LjEwOCA3NzkuMTcsNzQ5LjIxOSBDIDEzOS4xMzYsLTI2OC4zNzMgODcuNzAzLC0xMjEuMTkxIDAsMCIKICAgICAgIHN0eWxlPSJmaWxsOiMxMTI5NjQ7ZmlsbC1vcGFjaXR5OjE7ZmlsbC1ydWxlOm5vbnplcm87c3Ryb2tlOm5vbmUiCiAgICAgICB0cmFuc2Zvcm09Im1hdHJpeCgxLjMzMzMzMzMsMCwwLC0xLjMzMzMzMzMsNDg3My40MDY1LDc2NC4yMDgyNykiCiAgICAgICBjbGlwLXBhdGg9InVybCgjY2xpcFBhdGg4MCkiIC8+CiAgPC9nPgo8L3N2Zz4K'
                : 'dashicons-admin-generic';

            $this->page_hook = add_menu_page(
                $main_menu_title,
                $main_menu_title,
                $capability,
                $parent_slug,
                [$this, 'payment_list_page'],
                $menu_icon_url
            );

            $submenu_list = $this->get_submenu_list();

            if (empty($submenu_list)) return;

            foreach ($submenu_list as $submenu_title) {
                add_submenu_page(
                    $parent_slug,
                    ucwords(str_replace(['-', '_'], ' ', $submenu_title)),
                    ucwords(str_replace(['-', '_'], ' ', $submenu_title)),
                    'manage_options',
                    $parent_slug . '-' . strtolower(str_replace(['_', ' '], '-', $submenu_title)),
                    [$this, strtolower($submenu_title) . '_page'],
                );
            }
        }

        public function payment_list_page()
        {
            if (!current_user_can('manage_options')) {
                wp_die(esc_html__('You do not have permission to view this page.', 'ownpay-payment-gateway'));
            }

            $file_name = 'class-opwc-payment-list.php';
            $this->include_template_file($file_name);

            if (class_exists('OPWC_Payment_List')) {
                $menu_class = new OPWC_Payment_List();
                $menu_class->menu_page();
            }
        }

        /**
         * Enqueue scripts only on the OwnPay admin page
         */
        public function enqueue_page_scripts($hook)
        {
            if ($hook !== $this->page_hook) {
                return;
            }
            wp_enqueue_script(
                $this->plugin_name . '-admin-payment-list',
                plugin_dir_url(__FILE__) . 'js/opwc-payment-list.js',
                ['jquery'],
                $this->version,
                true
            );
        }

        public function include_template_file($file_name)
        {
            $file_name = basename($file_name);
            $template_file = plugin_dir_path(dirname(__FILE__)) . 'admin/partials/' . $file_name;

            if (file_exists($template_file)) {
                include $template_file;
            }
        }
    }
}
