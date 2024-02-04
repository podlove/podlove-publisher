import {
  PodloveLicense,
  PodloveLicenseOptionCommercial,
  PodloveLicenseOptionModification,
  PodloveLicenseVersion,
  PodloveLicenseOptionJurisdication,
} from '../types/license.types'

import pdImage from '../assets/pd.png'
import pdMarkImage from '../assets/pdmark.png'
import cc_0_0_Image from '../assets/0_0.png'
import cc_0_1_Image from '../assets/0_1.png'
import cc_1_0_Image from '../assets/1_0.png'
import cc_1_1_Image from '../assets/1_1.png'
import cc_10_0_Image from '../assets/10_0.png'
import cc_10_1_Image from '../assets/10_1.png'

export function getLicenseUrl(input: PodloveLicense): string | null {
  if (input.type === null || input.type != 'cc') return null
  if (input.version === PodloveLicenseVersion.cc0)
    return 'http://creativecommons.org/publicdomain/zero/1.0/'
  if (input.version === PodloveLicenseVersion.pdmark)
    return 'http://creativecommons.org/publicdomain/mark/1.0/'
  if (input.version === PodloveLicenseVersion.cc3) {
    let cc3 = 'http://creativecommons.org/licenses/by'
    if (input.optionCommercial === PodloveLicenseOptionCommercial.no) cc3 = cc3 + '-nc'
    if (input.optionModification === PodloveLicenseOptionModification.yes) cc3 = cc3 + '/'
    else if (input.optionModification === PodloveLicenseOptionModification.no) cc3 = cc3 + '-nd/'
    else cc3 = cc3 + '-sa/'
    if (input.optionJurisdication === null || input.optionJurisdication.symbol === 'international')
      cc3 = cc3 + '3.0/'
    else
      cc3 = cc3 + input.optionJurisdication.version + '/' + input.optionJurisdication.symbol + '/'
    return cc3 + 'deed.en'
  }
  if (input.version === PodloveLicenseVersion.cc4) {
    let cc4 = 'http://creativecommons.org/licenses/by'
    if (input.optionCommercial == PodloveLicenseOptionCommercial.no) cc4 = cc4 + '-nc'
    if (input.optionModification == PodloveLicenseOptionModification.yes) cc4 = cc4 + '/'
    else if (input.optionModification == PodloveLicenseOptionModification.no) cc4 = cc4 + '-nd/'
    else cc4 = cc4 + '-sa/'
    return cc4 + '4.0'
  }
  return null
}

export function getImageUrl(input: PodloveLicense, baseUrl: string): string | null {
  if (input.type === null || input.type !== 'cc') return null
  if (input.version === PodloveLicenseVersion.cc0)
    return pdImage;
  if (input.version === PodloveLicenseVersion.pdmark)
    return pdMarkImage;
  if (input.optionModification === null || input.optionCommercial === null) return null
  switch(input.optionModification) {
    case PodloveLicenseOptionModification.no:
      return input.optionCommercial === PodloveLicenseOptionCommercial.no ? cc_0_0_Image : cc_0_1_Image
    case PodloveLicenseOptionModification.yes:
      return input.optionCommercial === PodloveLicenseOptionCommercial.no ? cc_1_0_Image : cc_1_1_Image
    case PodloveLicenseOptionModification.yesbutshare:
      return input.optionCommercial === PodloveLicenseOptionCommercial.no ? cc_10_0_Image : cc_10_1_Image
  }
}

export function getLicenseFromUrl(url: string): PodloveLicense {
  const urlLowerCase = url.toLowerCase()
  // only parse cc licenses
  if (urlLowerCase.indexOf('creativecommons.org') < 0) {
    return {
      type: null,
      version: null,
      optionCommercial: null,
      optionModification: null,
      optionJurisdication: null,
    } as PodloveLicense
  }

  let version: PodloveLicenseVersion | null = null

  if (urlLowerCase.indexOf('/publicdomain/zero/') >= 0) {
    version = PodloveLicenseVersion.cc0
  } else {
    if (urlLowerCase.indexOf('/publicdomain/mark/') >= 0) {
      version = PodloveLicenseVersion.pdmark
    } else {
      if (urlLowerCase.indexOf('/4.0') >= 0) {
        version = PodloveLicenseVersion.cc4
      } else {
        version = PodloveLicenseVersion.cc3
      }
    }
  }

  const urlData = urlLowerCase.split('/').slice(4)

  let commercial: PodloveLicenseOptionCommercial = PodloveLicenseOptionCommercial.yes
  if (urlData[0].includes('nc')) {
    commercial = PodloveLicenseOptionCommercial.no
  }

  let modification: PodloveLicenseOptionModification = PodloveLicenseOptionModification.yes
  if (urlData[0].includes('sa')) {
    modification = PodloveLicenseOptionModification.yesbutshare
  } else {
    if (urlData[0].includes('nd')) {
      modification = PodloveLicenseOptionModification.no
    }
  }

  let jurisdication = PodloveLicenseOptionJurisdication[0]
  if (urlData.length > 2) {
    const idx: number = PodloveLicenseOptionJurisdication.findIndex(
      (item) => item.symbol === urlData[2]
    )
    if (idx > 0) jurisdication = PodloveLicenseOptionJurisdication[idx]
  }

  return {
    type: 'cc',
    version: version,
    optionCommercial: commercial,
    optionModification: modification,
    optionJurisdication: jurisdication,
  } as PodloveLicense
}
