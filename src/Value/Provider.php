<?php

namespace Romandots\Smser\Value;

enum Provider: string
{
    case MTS = "МТС";
    case BEELINE = "Билайн";
    case MEGAFON = "Мегафон";
    case TELE2 = "Tele2";
}