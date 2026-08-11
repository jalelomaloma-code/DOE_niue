<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\ContentStatus;
use App\Models\DocumentCategory;
use App\Models\Page;
use App\Support\RichTextSanitiser;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required()->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                // Every rule here exists because the alternative is a page the
                // CMS lists as Published that 404s on the site with nothing
                // logged. The route's constraint (Page::pathRoutePattern()) is
                // the thing being mirrored — see Page's constants.
                TextInput::make('slug')->required()->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Lower-case letters, numbers and hyphens only. This becomes part of the page address.')
                    ->regex('/^'.Page::SLUG_PATTERN.'$/')
                    ->validationMessages([
                        'regex' => 'The slug can only use lower-case letters, numbers and hyphens. Capital letters, spaces and underscores are not allowed, because the website address cannot contain them.',
                    ])
                    ->rule(fn (Get $get) => function (string $attribute, $value, $fail) use ($get) {
                        if (! is_string($value)) {
                            return;
                        }

                        // Exact matches stay unconditional, at every level. These
                        // are the names of system routes, and the cost of a page
                        // anywhere in the tree being called `admin` or `login` is
                        // confusion for the Department, which is worth more than
                        // the one slug it takes away.
                        if (in_array($value, Page::RESERVED_SLUGS, true)) {
                            $fail("The slug \"{$value}\" is reserved and would conflict with a system route.");

                            return;
                        }

                        $parentPath = filled($get('parent_id'))
                            ? Page::query()->whereKey($get('parent_id'))->value('path')
                            : null;
                        $path = $parentPath ? $parentPath.'/'.$value : $value;

                        if (Page::pathIsReserved($path)) {
                            $fail("The page address \"{$path}\" is reserved for an existing website section.");

                            return;
                        }

                        // The prefix rule is different, and has to be anchored the
                        // way the ROUTE is anchored. Page::pathRoutePattern()'s
                        // negative lookahead sits at the start of the whole path,
                        // not at the start of each segment, so /about/administration
                        // matches the catch-all perfectly well -- only a TOP-LEVEL
                        // slug beginning with a reserved prefix is unreachable.
                        // Applied to children as well, this rejected a legitimate
                        // About-Us child page with a message ("The website could not
                        // open this page") that was untrue of that page. Scoped to
                        // match the route rather than reworded, because the rule was
                        // wrong, not just its wording.
                        //
                        // A child moved back to the top level re-runs this: Filament
                        // validates the whole form on every save, and $get() reads
                        // the parent_id being submitted, not the stored one.
                        if (filled($get('parent_id'))) {
                            return;
                        }

                        // Prefix, not equality: the catch-all's lookahead is
                        // prefix-based, so "administration" and
                        // "storage-facilities" are just as unreachable as
                        // "admin" and "storage".
                        foreach (Page::ROUTE_EXCLUDED_PREFIXES as $prefix) {
                            if (str_starts_with($value, $prefix)) {
                                $fail("The slug \"{$value}\" starts with \"{$prefix}\", which is reserved for the system, so a top-level page cannot use it. Choose a slug that begins with a different word, or place this page under a parent page.");

                                return;
                            }
                        }
                    }),

                // ignoreRecord: true excludes the page being edited from its own
                // parent options -- cheap self-exclusion. Descendants are NOT
                // filtered out here (Filament has no built-in "exclude subtree"
                // option); the ->rule() below is what actually catches a
                // descendant being picked, self or otherwise.
                Select::make('parent_id')->relationship('parent', 'title', ignoreRecord: true)->searchable()->nullable()
                    // Usability half of the cyclic-parent guard: Page::guardAgainstCyclicParent()
                    // throws a raw InvalidArgumentException from the `saving` model event, and
                    // Filament does not convert arbitrary domain exceptions into inline field
                    // errors -- an admin would get an error page instead of a validation message.
                    // This walks the same ancestry chain and reports a field error instead. The
                    // model guard stays as the real control (defence in depth); this is only the
                    // friendly front door.
                    ->rule(function (?Page $record) {
                        return function (string $attribute, $value, $fail) use ($record) {
                            if ($value === null || $record === null || ! $record->exists) {
                                return;
                            }

                            $ancestorId = $value;
                            $seen = [];

                            while ($ancestorId !== null) {
                                if ($ancestorId === $record->id) {
                                    $fail('A page cannot be its own parent, or a descendant of itself.');

                                    return;
                                }

                                if (isset($seen[$ancestorId])) {
                                    break;
                                }
                                $seen[$ancestorId] = true;

                                $ancestorId = Page::query()->whereKey($ancestorId)->value('parent_id');
                            }
                        };
                    })
                    // Depth ceiling. The catch-all route serves Page::MAX_DEPTH
                    // segments; a grandchild's computed path has three and
                    // 404s silently. Two directions get you there, and the
                    // Select offers every page, so both are checked:
                    //   (a) picking a parent that already has a parent;
                    //   (b) giving a parent to a page that has children of its
                    //       own, which pushes those children down a level.
                    // (b) is not reachable on create -- a new page has no
                    // children yet -- but is trivially reachable on edit.
                    ->rule(function (?Page $record) {
                        return function (string $attribute, $value, $fail) use ($record) {
                            if ($value === null) {
                                return;
                            }

                            if (Page::query()->whereKey($value)->value('parent_id') !== null) {
                                $fail('The website supports two levels of pages, so the parent must be a top-level page. The page you chose already sits under another page.');

                                return;
                            }

                            if ($record?->exists && $record->children()->exists()) {
                                $fail('The website supports two levels of pages. This page already has pages beneath it, so it must stay at the top level. Move those pages elsewhere first.');
                            }
                        };
                    }),

                Textarea::make('intro')->rows(2)->maxLength(300)
                    ->helperText('One or two sentences. Shown under the title and in section listings.'),

                Builder::make('content')->blocks([
                    Builder\Block::make('rich_text')->label('Text')->schema([
                        TextInput::make('heading')->maxLength(255),
                        // maxLength for the same reason as the three model-level
                        // bodies (Programme, NewsArticle, Project): the
                        // sanitiser's own byte cap truncated silently and could
                        // blank the field outright on a multibyte boundary, so
                        // it was removed and the ceiling moved somewhere that
                        // reports. See RichTextSanitiser::MAX_LENGTH.
                        RichEditor::make('body')->required()
                            ->maxLength(RichTextSanitiser::MAX_LENGTH)
                            ->dehydrateStateUsing(fn (?string $state) => RichTextSanitiser::sanitise($state)),
                    ]),
                    Builder\Block::make('image')->schema([
                        FileUpload::make('url')->image()->required()->disk('public')->directory('page-images')->maxSize(5120),
                        TextInput::make('alt')->required()->maxLength(255)
                            ->helperText('Describe the image for screen readers. Required.'),
                        TextInput::make('caption')->maxLength(255),
                    ]),
                    Builder\Block::make('callout')->schema([
                        TextInput::make('heading')->maxLength(255),
                        Textarea::make('body')->required()->rows(3),
                        Select::make('tone')->options(['info' => 'Information', 'warning' => 'Warning'])->default('info')->required(),
                    ]),
                    Builder\Block::make('card_grid')->label('Card grid')->schema([
                        TextInput::make('heading')->maxLength(255),
                        Repeater::make('cards')->schema([
                            TextInput::make('title')->required()->maxLength(255),
                            Textarea::make('text')->rows(2),
                            TextInput::make('url')->maxLength(255)
                                ->regex('/^(\/(?!\/)\S*|https?:\/\/\S+)$/')
                                ->helperText('A site-relative path, e.g. /waste-and-recycling'),
                        ])->minItems(1),
                    ]),
                    Builder\Block::make('documents_list')->label('Documents list')->schema([
                        TextInput::make('heading')->maxLength(255),
                        Select::make('category_id')->label('Category')
                            // NOT ->relationship() -- a Builder block is not a relation, it is a
                            // JSON blob. ->options() alone is correct here.
                            ->options(fn () => DocumentCategory::orderBy('sort_order')->pluck('name', 'id'))
                            ->helperText('Leave blank to list documents from every category.'),
                        TextInput::make('limit')->numeric()->default(10)->minValue(1)->maxValue(50),
                    ]),
                    Builder\Block::make('programmes_list')->label('Programmes list')->schema([
                        TextInput::make('heading')->maxLength(255),
                        TextInput::make('limit')->numeric()->default(12)->minValue(1)->maxValue(50),
                    ]),
                    Builder\Block::make('contact_details')->label('Contact details')->schema([
                        TextInput::make('heading')->maxLength(255),
                    ]),
                    Builder\Block::make('team_grid')->label('Team grid')->schema([
                        TextInput::make('heading')->maxLength(255),
                    ]),
                ])->collapsible()->blockNumbers(false),

                Toggle::make('show_in_section_nav')->default(true)
                    ->helperText('Show this page in its section listing. Does not affect the main menu.'),

                // Spec 2 criterion 2 requires the Department to be able to
                // reorder pages without a developer, and until this field
                // existed there was no way to: every CMS-created page took the
                // column default of 0 and PagesTable showed sort_order
                // read-only.
                //
                // Deliberately a number field rather than ->reorderable() on
                // PagesTable, which is what the flat tables (NavigationItems,
                // TeamMembers, QuickLinks, the two category resources) use.
                // Two reasons, both from Filament's own behaviour:
                //   1. While reorder mode is on, Filament forces the table sort
                //      to the reorder column (CanSortRecords::applySortingToTableQuery),
                //      discarding PagesTable's ->defaultSort('path') -- and
                //      `path` is the only thing that renders that table as a
                //      readable tree rather than a scrambled flat list.
                //   2. Worse, reorderTable() renumbers every dragged row 1..N
                //      across the whole result set with no notion of parent_id.
                //      For Pages, sort_order is an ordinal WITHIN a sibling
                //      group, so one drag on the unfiltered table would rewrite
                //      the order of every section at once, breaking today's
                //      all-zero ties in whatever arbitrary order the database
                //      returned. That is safe on a flat list, where "global
                //      1..N" and "position in the group" are the same number;
                //      Pages is the one hierarchical table, which is exactly
                //      why it is the one that must not drag.
                TextInput::make('sort_order')
                    ->label('Order in section')
                    ->numeric()
                    ->integer()
                    ->required()
                    ->default(0)
                    ->helperText('Orders this page against the other pages in the same section: lower numbers appear first. Pages outside this section are unaffected.'),

                // This closure IS the publish control. Filament derives a
                // server-side `in:` rule from whichever options it returns,
                // and re-evaluates it per request against the acting user --
                // so an Editor who posts `published` anyway is rejected by
                // Laravel's own validation, not by a check further in.
                // Weaken the mayPublish() test below and the enforcement goes
                // with it; there is no second line of defence. PagePolicy
                // deliberately has no publish() ability for this reason.
                Select::make('status')
                    ->options(function (): array {
                        $options = ContentStatus::options();

                        if (! (auth()->user()?->mayPublish() ?? false)) {
                            unset($options[ContentStatus::Published->value]);
                        }

                        return $options;
                    })
                    ->default(ContentStatus::Draft->value)
                    ->required(),

                DateTimePicker::make('published_at'),
                TextInput::make('seo_title')->maxLength(255),
                Textarea::make('seo_description')->rows(2)->maxLength(300),
            ]);
    }
}
