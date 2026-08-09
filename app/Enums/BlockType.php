<?php

namespace App\Enums;

enum BlockType: string
{
    case RichText = 'rich_text';
    case Image = 'image';
    case Callout = 'callout';
    case CardGrid = 'card_grid';
    case DocumentsList = 'documents_list';
    case ProgrammesList = 'programmes_list';
    case ContactDetails = 'contact_details';
    case TeamGrid = 'team_grid';

    public function label(): string
    {
        return match ($this) {
            self::RichText => 'Text',
            self::Image => 'Image',
            self::Callout => 'Callout',
            self::CardGrid => 'Card grid',
            self::DocumentsList => 'Documents list',
            self::ProgrammesList => 'Programmes list',
            self::ContactDetails => 'Contact details',
            self::TeamGrid => 'Team grid',
        };
    }

    /** The Blade component rendering this block. */
    public function view(): string
    {
        return 'components.blocks.'.str_replace('_', '-', $this->value);
    }
}
