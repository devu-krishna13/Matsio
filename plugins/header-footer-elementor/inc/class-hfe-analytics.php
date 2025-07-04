<?php
/**
 * HFE Analytics.
 *
 * @package HFE
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * HFE Analytics.
 *
 * HFE Analytics. handler class is responsible for rolling back HFE to
 * previous version.
 *
 * @since 2.3.0
 */
if ( ! class_exists( 'HFE_Analytics' ) ) {

	class HFE_Analytics {

		/**
		 * HFE Analytics constructor.
		 *
		 * Initializing HFE Analytics.
		 *
		 * @since 2.3.0
		 * @access public
		 *
		 * @param array $args Optional. HFE Analytics arguments. Default is an empty array.
		 */
		public function __construct() {
			add_action( 'admin_init', [ $this, 'maybe_migrate_analytics_tracking' ] );
			// BSF Analytics Tracker.
			if ( ! class_exists( 'BSF_Analytics_Loader' ) ) {
				require_once HFE_DIR . 'admin/bsf-analytics/class-bsf-analytics-loader.php';
			}

			$bsf_analytics = BSF_Analytics_Loader::get_instance();

			$bsf_analytics->set_entity(
				[
					'uae' => [
						'product_name'        => 'Ultimate Addons for Elementor',
						'path'                => HFE_DIR . 'admin/bsf-analytics',
						'author'              => 'Ultimate Addons for Elementor',
						'time_to_display'     => '+24 hours',
						'deactivation_survey' => [
							[
								'id'                => 'deactivation-survey-header-footer-elementor', // 'deactivation-survey-<your-plugin-slug>'
								'popup_logo'        => HFE_URL . 'assets/images/settings/logo.svg',
								'plugin_slug'       => 'header-footer-elementor', // <your-plugin-slug>
								'plugin_version'    => HFE_VER,
								'popup_title'       => 'Quick Feedback',
								'support_url'       => 'https://ultimateelementor.com/contact/',
								'popup_description' => 'If you have a moment, please share why you are deactivating Ultimate Addons for Elementor:',
								'show_on_screens'   => [ 'plugins' ],
							],
						],
						'hide_optin_checkbox' => true,
					],
				]
			);
			
			add_filter( 'bsf_core_stats', [ $this, 'add_uae_analytics_data' ] );
		}

		/**
		 * Migrates analytics tracking option from 'bsf_analytics_optin' to 'uae_analytics_optin'.
		 *
		 * Checks if the old analytics tracking option ('bsf_analytics_optin') is set to 'yes'
		 * and if the new option ('uae_analytics_optin') is not already set.
		 * If so, updates the new tracking option to 'yes' to maintain user consent during migration.
		 *
		 * @since 2.3.2
		 * @access public
		 *
		 * @return void
		 */
		public function maybe_migrate_analytics_tracking() {
			$old_tracking = get_option( 'bsf_analytics_optin', false );
			$new_tracking = get_option( 'uae_analytics_optin', false );
			if ( 'yes' === $old_tracking && false === $new_tracking ) {
				update_option( 'uae_analytics_optin', 'yes' );
				$time = get_option('bsf_analytics_installed_time');
				update_option( 'bsf_analytics_installed_time' , $time );
			}
		}
        
        /**
         * Callback function to add specific analytics data.
         *
         * @param array $stats_data existing stats_data.
         * @since 2.3.0
         * @return array
         */
        public function add_uae_analytics_data( $stats_data ) {
			 // Check if $stats_data is empty or not an array.
			 if ( empty( $stats_data ) || ! is_array( $stats_data ) ) {
				$stats_data = []; // Initialize as an empty array.
			}
		
            $stats_data['plugin_data']['uae']		= [
                'free_version'  => HFE_VER,
                'pro_version' => ( defined( 'UAEL_VERSION' ) ? UAEL_VERSION : '' ),
                'site_language' => get_locale(),
                'elementor_version' => ( defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '' ),
                'elementor_pro_version' => ( defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : '' ),
                'onboarding_triggered' => ( 'yes' === get_option( 'hfe_onboarding_triggered' ) ) ? 'yes' : 'no',
				'uaelite_subscription' => ( 'done' === get_option( 'uaelite_subscription' ) ) ? 'yes' : 'no'
            ];

            $hfe_posts = get_posts( [
                'post_type'   => 'elementor-hf',
                'post_status' => 'publish',
                'numberposts' => -1
            ] );

            $stats_data['plugin_data']['uae']['numeric_values'] = [
                'total_hfe_templates'            => count( $hfe_posts ),
            ];

			$fetch_elementor_data = $this->hfe_get_widgets_usage();
			foreach ($fetch_elementor_data as $key => $value) {
				$stats_data['plugin_data']['uae']['numeric_values'][$key] = $value;
			}
            return $stats_data;
        }

		/**
		 * Fetch Elementor data.
		 */
		private function hfe_get_widgets_usage() {
				$get_Widgets = get_option( 'uae_widgets_usage_data_option', [] );
				return $get_Widgets;
		}
	}
}
new HFE_Analytics();