import { createApi } from '../sagas/api'
import * as auphonic from '@store/auphonic.store'
import { createPlusFileMigrationRootSaga } from '../shared/plusFileMigration.sagas'

export default createPlusFileMigrationRootSaga(createApi, () =>
  auphonic.setPlusTransferStatus({
    production_uuid: 'migration',
    status: 'completed',
  })
)
