<?php

declare(strict_types=1);

namespace Shimmie2;

class Automatic1111TaggerConfig extends ConfigGroup
{
    public const KEY = "automatic1111_tagger";

    #[ConfigMeta("API Endpoint", ConfigType::STRING, default: "http://localhost:7860/tagger/v1/interrogate")]
    public const API_ENDPOINT = 'automatic1111_tagger_api_endpoint';

    #[ConfigMeta("Model", ConfigType::STRING, default: "wd-v1-4-moat-tagger.v2")]
    public const MODEL = 'automatic1111_tagger_model';

    #[ConfigMeta("Threshold", ConfigType::STRING, default: "0.35")]
    public const THRESHOLD = 'automatic1111_tagger_threshold';
}
