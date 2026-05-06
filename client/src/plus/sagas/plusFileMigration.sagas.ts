import { createApi } from './api'
import { createPlusFileMigrationRootSaga } from '../../shared/plusFileMigration.sagas'

export default createPlusFileMigrationRootSaga(createApi)
