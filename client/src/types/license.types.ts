export enum PodloveLicenseVersion {
    cc0 = "Public Domain License",
    pdmark = "Public Domain Mark License",
    cc3 = "Creative Commons 3.0 and earlier",
    cc4 = "Creative Commons 4.0"
}

export enum PodloveLicenseOptionCommercial {
    yes = "Yes",
    no = "No"
}

export enum PodloveLicenseOptionModification {
    yes = "Yes",
    yesbutshare = "Yes, as long as others share alike",
    no = "No"
}

export enum PodloveLicenseScope {
    Episode = "Episode",
    Podcast = "Podcast"
}

export type PodloveJurisdicationObject = {
    symbol: string,
    name: string,
    version: string
}

export const PodloveLicenseOptionJurisdication: Array<PodloveJurisdicationObject> = [
    { symbol: "international", name: "International"      , version: "3.0" },
    { symbol: "ar",            name: "Argentina"          , version: "2.5" },
    { symbol: "au",            name: "Australia"          , version: "3.0" },
    { symbol: "at",            name: "Austria"            , version: "3.0" },
    { symbol: "be",            name: "Belgium"            , version: "2.0" },
    { symbol: "br",            name: "Brazil"             , version: "3.0" },
    { symbol: "bg",            name: "Bulgaria"           , version: "2.5" },
    { symbol: "ca",            name: "Canada"             , version: "2.5" },
    { symbol: "cl",            name: "Chile"              , version: "3.0" },
    { symbol: "cn",            name: "China Mainland"     , version: "3.0" },
    { symbol: "co",            name: "Colombia"           , version: "2.5" },
    { symbol: "cr",            name: "Costa Rica"         , version: "3.0" },
    { symbol: "hr",            name: "Croatia"            , version: "3.0" },
    { symbol: "cz",            name: "Czech Republic"     , version: "3.0" },
    { symbol: "dk",            name: "Denmark"            , version: "2.5" },
    { symbol: "ec",            name: "Ecuador"            , version: "3.0" },
    { symbol: "eg",            name: "Egypt"              , version: "3.0" },
    { symbol: "ee",            name: "Estonia"            , version: "3.0" },
    { symbol: "fi",            name: "Finland"            , version: "1.0" },
    { symbol: "fr",            name: "France"             , version: "3.0" },
    { symbol: "de",            name: "Germany"            , version: "3.0" },
    { symbol: "gr",            name: "Greece"             , version: "3.0" },
    { symbol: "gt",            name: "Guatemala"          , version: "3.0" },
    { symbol: "hk",            name: "Hong Kong"          , version: "3.0" },
    { symbol: "hu",            name: "Hungary"            , version: "2.5" },
    { symbol: "igo",           name: "IGO"                , version: "3.0" },
    { symbol: "in",            name: "India"              , version: "2.5" },
    { symbol: "ie",            name: "Ireland"            , version: "3.0" },
    { symbol: "il",            name: "Israel"             , version: "2.5" },
    { symbol: "it",            name: "Italy"              , version: "3.0" },
    { symbol: "jp",            name: "Japan"              , version: "2.1" },
    { symbol: "lu",            name: "Luxembourg"         , version: "3.0" },
    { symbol: "mk",            name: "Macedonia"          , version: "2.5" },
    { symbol: "my",            name: "Malaysia"           , version: "2.5" },
    { symbol: "mt",            name: "Malta"              , version: "2.5" },
    { symbol: "mx",            name: "Mexico"             , version: "2.5" },
    { symbol: "nl",            name: "Netherlands"        , version: "3.0" },
    { symbol: "nz",            name: "New Zealand"        , version: "3.0" },
    { symbol: "no",            name: "Norway"             , version: "3.0" },
    { symbol: "pe",            name: "Peru"               , version: "2.5" },
    { symbol: "ph",            name: "Philippines"        , version: "3.0" },
    { symbol: "pl",            name: "Poland"             , version: "3.0" },
    { symbol: "pt",            name: "Portugal"           , version: "3.0" },
    { symbol: "pr",            name: "Puerto Rico"        , version: "3.0" },
    { symbol: "ro",            name: "Romania"            , version: "3.0" },
    { symbol: "rs",            name: "Serbia"             , version: "3.0" },
    { symbol: "sg",            name: "Singapore"          , version: "3.0" },
    { symbol: "si",            name: "Slovenia"           , version: "2.5" },
    { symbol: "za",            name: "South Africa"       , version: "2.5" },
    { symbol: "kp",            name: "South Korea"        , version: "2.0" },
    { symbol: "es",            name: "Spain"              , version: "3.0" },
    { symbol: "se",            name: "Sweden"             , version: "2.5" },
    { symbol: "ch",            name: "Switzerland"        , version: "3.0" },
    { symbol: "tw",            name: "Taiwan"             , version: "3.0" },
    { symbol: "th",            name: "Thailand"           , version: "3.0" },
    { symbol: "gb",            name: "UK: England & Wales", version: "2.0" },
    { symbol: "gb_sc",         name: "UK: Scotland"       , version: "2.5" },
    { symbol: "ug",            name: "Uganda"             , version: "3.0" },
    { symbol: "us",            name: "United States"      , version: "3.0" },
    { symbol: "vn",            name: "Vietnam"            , version: "3.0" },
]

export interface PodloveLicense {
    type: string | null,
    version: PodloveLicenseVersion | null,
    optionCommercial: PodloveLicenseOptionCommercial | null,
    optionModification: PodloveLicenseOptionModification | null,
    optionJurisdication: PodloveJurisdicationObject | null,
}