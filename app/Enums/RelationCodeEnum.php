<?php

namespace App\Enums;

enum RelationCodeEnum: string
{
    case SPOUSE = 'relSpouse';
    case CHILD = 'relChild';
    case PARENT = 'relParent';
    case SIBLING_OR_RELATIVES = 'relSiblingOrRelatives';
    case DOMESTIC_WORKER = 'relDomesticWorker';
}
