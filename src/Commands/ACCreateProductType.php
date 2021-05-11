<?php

namespace Larapress\AdobeConnect\Commands;

use Illuminate\Console\Command;
use Larapress\ECommerce\Models\ProductType;

class ACCreateProductType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lp:ac:create-pt';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create AdobeConnect product type';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ProductType::updateOrCreate([
            'name' => config('larapress.adobeconnect.product_typename'),
            'author_id' => 1,
        ], [
            'flags' => 0,
            'data' => [
                "form" => [
                    "schema" => [
                        "id" => "sample_form",
                        "fields" => [
                            "servers" => [
                                "type" => "input",
                                "input" => "objects-list",
                                "id" => "servers",
                                "label" => trans('larapress::adobeconnect.product_type.servers_label'),
                                "decorator" => [
                                    "label" => "=>id#=>name",
                                    "labels" => "id,name"
                                ]
                            ],
                            "meeting_name" => [
                                "type" => "input",
                                "input" => "text",
                                "id" => "meeting_name",
                                "label" => trans('larapress::adobeconnect.product_type.meeting_name_label')
                            ],
                            "status" => [
                                "type" => "input",
                                "input" => "select",
                                "id" => "status",
                                "label" => trans('larapress::adobeconnect.product_type.status_label'),
                                "objects" => [
                                    [
                                        "id" => "ended",
                                        "title" => trans('larapress::adobeconnect.product_type.status_ended')
                                    ],
                                    [
                                        "id" => "live",
                                        "title" => trans('larapress::adobeconnect.product_type.status_live')
                                    ],
                                    [
                                        "id" => "not_started",
                                        "title" => trans('larapress::adobeconnect.product_type.status_notstarted')
                                    ]
                                ]
                            ]
                        ],
                        "options" => [
                            "type" => "col"
                        ]
                    ],
                    "code" => [],
                    "values" => [],
                    "template" => [
                        "name" => null
                    ]
                ],
                "title" => trans('larapress::adobeconnect.product_type.title'),
                "agent" => "pages.vuetify.1.0"
            ]
        ]);
        $this->info("Done.");

        return 0;
    }
}
