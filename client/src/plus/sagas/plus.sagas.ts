import { createApi } from './api'
import { createPlusRootSaga } from '../../shared/plus.sagas'

export default createPlusRootSaga(createApi)
