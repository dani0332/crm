// Imports: Dependencies
import { combineReducers } from 'redux';
import loginReducer from './login'

// Redux: Root Reducer
const rootReducer = combineReducers({
  login: loginReducer,
});

// Exports
export default rootReducer;
