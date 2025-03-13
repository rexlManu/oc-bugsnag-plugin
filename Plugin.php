<?php namespace RexlManu\BugSnag;

use Backend;
use Bugsnag\Client;
use Bugsnag\Configuration;
use Event;
use Log;
use MeadSteve\MonoSnag\BugsnagHandler;
use System\Classes\PluginBase;
use VojtaSvoboda\ErrorLogger\Models\Settings;

class Plugin extends PluginBase
{
    public $require = [
        'VojtaSvoboda.ErrorLogger',
    ];

    public function pluginDetails()
    {
        return [
            'name' => 'rexlmanu.bugsnag::lang.plugin.name',
            'description' => 'rexlmanu.bugsnag::lang.plugin.description',
            'author' => 'Vojta Svoboda',
            'icon' => 'icon-bug',
        ];
    }

    public function boot()
    {
        // register Bugsnag handler
        $isLaravel56OrUp = method_exists(\Illuminate\Log\Logger::class, 'getLogger');
        $monolog = $isLaravel56OrUp ? Log::getLogger() : Log::getMonolog();
        $this->setBugsnagHandler($monolog);

        // extend ErrorLogger settings form
        Event::listen('backend.form.extendFields', function($widget) {
            if (!$widget->model instanceof Settings) {
                return;
            }

            $widget->addTabFields([
                'bugsnag_enabled' => [
                    'tab' => 'rexlmanu.bugsnag::lang.tab.name',
                    'label' => 'rexlmanu.bugsnag::lang.fields.bugsnag_enabled.label',
                    'type' => 'switch',
                ],
                'bugsnag_api_key' => [
                    'tab' => 'rexlmanu.bugsnag::lang.tab.name',
                    'label' => 'rexlmanu.bugsnag::lang.fields.bugsnag_api_key.label',
                    'comment' => 'rexlmanu.bugsnag::lang.fields.bugsnag_api_key.comment',
                    'required' => true,
                    'trigger' => [
                        'action' => 'show',
                        'field' => 'bugsnag_enabled',
                        'condition' => 'checked',
                    ],
                ],
            ]);
        });
    }

    /**
     * Set Bugsnag handler.
     *
     * @param $monolog
     *
     * @return mixed
     */
    protected function setBugsnagHandler($monolog)
    {
        $required = ['bugsnag_enabled', 'bugsnag_api_key'];
        if (!$this->checkRequiredFields($required)) {
            return $monolog;
        }

        $api_key = Settings::get('bugsnag_api_key');
        $configuration = new Configuration($api_key);
        $client = new Client($configuration);
        $monolog->pushHandler(new BugsnagHandler($client));
    }

    /**
     * Check handler required fields.
     *
     * @param array $fields
     *
     * @return bool
     */
    private function checkRequiredFields(array $fields)
    {
        foreach ($fields as $field) {
            $value = Settings::get($field);
            if (!$value || empty($value)) {
                return false;
            }
        }

        return true;
    }
}
