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

export interface PodloveLicense {
    type: string | null,
    version: PodloveLicenseVersion | null,
    optionCommercial: PodloveLicenseOptionCommercial | null,
    optionModification: PodloveLicenseOptionModification | null,
    optionJurisdication: string | null
}