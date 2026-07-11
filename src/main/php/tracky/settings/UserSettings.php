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
            new Section("settings.section.localization.label"),
            new Select(SettingName::LANGUAGE, "settings.localization.language.label", "auto", [
                "auto" => "settings.localization.language.option.auto",
                null,
                "de" => "settings.localization.language.option.de",
                "en" => "settings.localization.language.option.en"
            ]),

            new Section("settings.section.overview.label"),
            new Number(SettingName::OVERVIEW_MAX_EPISODES, "settings.overview.max-episodes.label", default: 8, min: 4, max: 16, suffixLabel: "settings.items.label"),
            new Number(SettingName::OVERVIEW_MAX_MOVIES, "settings.overview.max-movies.label", default: 8, min: 4, max: 16, suffixLabel: "settings.items.label"),
            new Number(SettingName::OVERVIEW_MAX_NEXT_EPISODE_SHOWS, "settings.overview.max-next-episode-shows.label", default: 8, min: 4, max: 16, suffixLabel: "settings.items.label"),

            new Section("settings.section.shows.label"),
            new Number(SettingName::SHOWS_MAX_EPISODES, "settings.shows.max-episodes.label", default: 10, min: 5, max: 60, suffixLabel: "settings.items.label"),
            new Checkbox(SettingName::HIDE_SHOWS, "settings.shows.hide-shows.label", options: [
                "ended"    => "settings.shows.hide-shows.option.ended",
                "finished" => "settings.shows.hide-shows.option.finished",
                "unwatched" => "settings.shows.hide-shows.option.unwatched",
            ]),

            new Section("settings.section.user-profile.label"),
            new Number(SettingName::PROFILE_HISTORY_ITEMS_PER_PAGE, "settings.user-profile.history-items-per-page.label", default: 20, min: 4, max: 100, suffixLabel: "settings.items.label"),
            new Number(SettingName::PROFILE_HISTORY_MAX_PREVIOUS_NEXT_PAGES, "settings.user-profile.history-max-previous-next-pages.label", default: 3, min: 1, max: 10, suffixLabel: "settings.items.label"),

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

    /**
     * @return array<string, list<Type>>
     */
    public function getOptionsGroupedBySections(): array
    {
        $perSectionOptions = [];
        $section = null;

        foreach ($this->options as $option) {
            if ($option instanceof Section) {
                $section = $option->getName();
                $perSectionOptions[$section] = [
                    "section" => $option,
                    "options" => []
                ];
            } elseif ($section !== null) {
                $perSectionOptions[$section]["options"][] = $option;
            }
        }

        return $perSectionOptions;
    }

    public function getOptionValue(SettingName $name)
    {
        return $this->getOption($name->value)?->getValue();
    }
}
