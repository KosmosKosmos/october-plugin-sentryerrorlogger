<?php

    namespace KosmosKosmos\SentryErrorLogger;

    use Sentry\ClientBuilder;
    use Sentry\Monolog\Handler;
    use Sentry\State\Hub;
    use System\Classes\PluginBase;
    use VojtaSvoboda\ErrorLogger\Models\Settings;

    use Log;

    class Plugin extends PluginBase {

        public $require = ['VojtaSvoboda.ErrorLogger'];

        public function pluginDetails() {
            return [
                'name'        => 'kosmoskosmos.sentryerrorlogger::lang.plugin.name',
                'description' => 'kosmoskosmos.sentryerrorlogger::lang.plugin.description',
                'icon'        => 'icon-bug',
                'homepage'    => 'https://github.com/skydiver/'
            ];
        }

        public function boot() {

            $isLaravel56OrUp = method_exists(\Illuminate\Log\Logger::class, 'getLogger');
            $monolog = $isLaravel56OrUp ? Log::getLogger() : Log::getMonolog();

            $this->setSentryHandler($monolog);

            \Event::listen('backend.form.extendFields', function($widget) {

                if(!$widget->model instanceof \VojtaSvoboda\ErrorLogger\Models\Settings) {
                    return;
                }

                $widget->addTabFields([

                    'sentry_enabled' => [
                        'tab'     => 'kosmoskosmos.sentryerrorlogger::lang.tab.name',
                        'label'   => 'kosmoskosmos.sentryerrorlogger::lang.fields.sentry_enabled.label',
                        'type'    => 'switch'
                    ],

                    'sentry_dsn' => [
                        'tab'      => 'kosmoskosmos.sentryerrorlogger::lang.tab.name',
                        'label'    => 'kosmoskosmos.sentryerrorlogger::lang.fields.sentry_dsn.label',
                        'required' => true,
                        'trigger'  => [
                            'action'    => 'show',
                            'field'     => 'sentry_enabled',
                            'condition' => 'checked'
                        ]
                    ],

                    'sentry_level' => [
                        'tab'      => 'kosmoskosmos.sentryerrorlogger::lang.tab.name',
                        'label'    => 'kosmoskosmos.sentryerrorlogger::lang.fields.sentry_level.label',
                        'required' => true,
                        'type'     => 'dropdown',
                        'options'  => \VojtaSvoboda\ErrorLogger\Models\Settings::getErrorLevelOptions(),
                        'trigger'  => [
                            'action'    => 'show',
                            'field'     => 'sentry_enabled',
                            'condition' => 'checked'
                        ]
                    ],



                ]);

            });

        }

        private function setSentryHandler($monolog) {

            $required = ['sentry_enabled', 'sentry_dsn'];

            if(!$this->checkRequiredFields($required)) {
                return $monolog;
            }

            $dsn = Settings::get('sentry_dsn'  , null);
            $level = Settings::get('sentry_level', 100);
            $client = ClientBuilder::create(['dsn' => $dsn])->getClient();
            $handler = new Handler(new Hub($client), $level);

            $monolog->pushHandler($handler);

            return $monolog;

        }

        private function checkRequiredFields(array $fields) {
            foreach ($fields as $field) {
                $value = Settings::get($field);
                if (!$value || empty($value)) {
                    return false;
                }
            }
            return true;
        }

    }

?>
