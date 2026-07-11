<?php
namespace tracky\settings;

use tracky\settings\type\Checkbox;
use tracky\settings\type\Number;
use tracky\settings\type\Section;
use tracky\settings\type\Select;
use tracky\settings\type\Type;

class UserSettings
{
    /**
     * @var array<string, Type>
     */
    private array $options = [];

    public function __construct()
    {
        $options = [
            new Section("localization", "settings.section.localization.label"),
            new Select("language", "settings.localization.language.label", "auto", [
                "auto" => "settings.localization.language.option.auto",
                null,
                "de" => "settings.localization.language.option.de",
                "en" => "settings.localization.language.option.en"
            ]),

            new Section("overview", "settings.section.overview.label"),
            new Number("overviewMaxEpisodes", "settings.overview.max-episodes.label", default: 8, min: 4, max: 16),
            new Number("overviewMaxMovies", "settings.overview.max-movies.label", default: 8, min: 4, max: 16),
            new Number("overviewMaxNextEpisodeShows", "settings.overview.max-next-episode-shows.label", default: 8, min: 4, max: 16),

            new Section("shows", "settings.section.shows.label"),
            new Number("showsMaxEpisodes", "settings.shows.max-episodes.label", default: 10, min: 5, max: 60),
            new Checkbox("hideShows", "settings.shows.hide-shows.label", options: [
                "ended"    => "settings.shows.hide-shows.option.ended",
                "finished" => "settings.shows.hide-shows.option.finished",
                "unwatched" => "settings.shows.hide-shows.option.unwatched",
            ]),

            /*
            new Select("exampleSelect", "settings.example-select.label", default: "foo", options: [
                "foo" => "Foo",
                "bar" => "Bar",
                "baz" => "Baz"
            ]),
            new Text("exampleText", "settings.example-text.label", placeholder: "settings.example-text.placeholder", default: "anonymous", regex: "^[a-zA-Z0-9\-_]{3,20}$"),
            new Radio("exampleRadio", "settings.example-radio.label", default: "foo", options: [
                "foo" => "Foo",
                "bar" => "Bar"
            ]),
            */
        ];

        foreach ($options as $option) {
            $this->options[$option->getName()] = $option;
        }
    }

    public function getOption(string $name): ?Type
    {
        return $this->options[$name] ?? null;
    }

    /**
     * @return array<string, Type>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOptionValue(string $name)
    {
        return $this->getOption($name)?->getValue();
    }
}
