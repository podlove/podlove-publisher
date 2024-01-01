
import { PodloveLicense, PodloveLicenseOptionCommercial, PodloveLicenseOptionModification, PodloveLicenseVersion } from '../types/license.types'

function imageUrlCommercialUseId(option: PodloveLicenseOptionCommercial | null) : string
{
  if (option == null)
    return ""
  if (option == PodloveLicenseOptionCommercial.no)
    return "0"
  else
    return "1"
}

function imageUrlModificationId(option: PodloveLicenseOptionModification | null ): string {
  if (option == null)
    return ""
  switch (option) {
    case PodloveLicenseOptionModification.yes:
      return "1"
    case PodloveLicenseOptionModification.yesbutshare:
      return "10"
    case PodloveLicenseOptionModification.no:
      return "0"
    default:
      return "1"
  }
}

export function getLicenseUrl(input: PodloveLicense) : string | null {
  if (input.type == null || input.type != "cc")
    return null
  if (input.version == PodloveLicenseVersion.cc0)
    return "http://creativecommons.org/publicdomain/zero/1.0/"
  if (input.version == PodloveLicenseVersion.pdmark)
    return "http://creativecommons.org/publicdomain/mark/1.0/"
  if (input.version == PodloveLicenseVersion.cc3) {
    let cc3 = "http://creativecommons.org/licenses/by"
    if (input.optionCommercial == PodloveLicenseOptionCommercial.no)
      cc3 = cc3 + "-nc"
    if (input.optionModification == PodloveLicenseOptionModification.yes)
      cc3 = cc3 + "/"
    else if (input.optionModification == PodloveLicenseOptionModification.no)
      cc3 = cc3 + "-nd/"
    else
      cc3 = cc3 + "-sa/"
    if (input.optionJurisdication == null || input.optionJurisdication.symbol == "international")
      cc3 = cc3 + "3.0/"
    else
      cc3 = cc3 + input.optionJurisdication.version + "/" + input.optionJurisdication.symbol + "/"
    return cc3 + "deed.en"
  }
  if (input.version == PodloveLicenseVersion.cc4) {
    let cc4 = "http://creativecommons.org/licenses/by"
    if (input.optionCommercial == PodloveLicenseOptionCommercial.no)
      cc4 = cc4 + "-nc"
    if (input.optionModification == PodloveLicenseOptionModification.yes)
      cc4 = cc4 + "/"
    else if (input.optionModification == PodloveLicenseOptionModification.no)
      cc4 = cc4 + "-nd/"
    else
      cc4 = cc4 + "-sa/"
    return cc4 + "4.0"
  }
  return null
}

export function getImageUrl(input: PodloveLicense, baseUrl: string) : string | null {
  if (input.type == null || input.type != "cc")
    return null
  if (input.version == PodloveLicenseVersion.cc0)
    return baseUrl + "/wp-content/plugins/podlove-publisher/images/cc/pd.png"
  if (input.version == PodloveLicenseVersion.pdmark)
    return baseUrl + "/wp-content/plugins/podlove-publisher/images/cc/pdmark.png"
  return baseUrl + "/wp-content/plugins/podlove-publisher/images/cc/" + imageUrlModificationId(input.optionModification) 
    + "_" + imageUrlCommercialUseId(input.optionCommercial) + ".png"
}

export function getLicenseFromUrl(url: string) : PodloveLicense {
  const urlLowerCase = url.toLowerCase()
  // only parse cc licenses
  if (urlLowerCase.indexOf("creativecommons.org") < 0) {
    return {
      type: null,
      version: null,
      optionCommercial: null,
      optionModification: null,
      optionJurisdication: null
    } as PodloveLicense
  }

  let version : PodloveLicenseVersion | null = null 

  if (urlLowerCase.indexOf("/publicdomain/zero/") >= 0) {
    version = PodloveLicenseVersion.cc0
  }
  else { 
    if (urlLowerCase.indexOf("/publicdomain/mark/") >= 0) {
      version = PodloveLicenseVersion.pdmark
    }
    else {
      if (urlLowerCase.indexOf("/4.0") >= 0) {
        version = PodloveLicenseVersion.cc4
      }
      else {
        version = PodloveLicenseVersion.cc3
      }
    }
  }

  const urlArray = urlLowerCase.split('/')
  const urlData = urlArray.slice(4)

  let commercial : PodloveLicenseOptionCommercial = PodloveLicenseOptionCommercial.yes
  if (urlData[0].indexOf("nc") >= 0) {
    commercial = PodloveLicenseOptionCommercial.no
  }

  let modification : PodloveLicenseOptionModification = PodloveLicenseOptionModification.yes
  if (urlData[0].indexOf("sa") >= 0) {
    modification = PodloveLicenseOptionModification.yesbutshare
  } else {
    if (urlData[0].indexOf("nd") >= 0) {
      modification = PodloveLicenseOptionModification.no
    }
  }

  return {
    type: "cc",
    version: version,
    optionCommercial: commercial,
    optionModification: modification,
    optionJurisdication: null
  } as PodloveLicense
}