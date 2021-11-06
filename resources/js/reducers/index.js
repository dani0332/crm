// Imports: Dependencies
import { combineReducers } from 'redux';
import loginReducer from './login';
import visibleFormReducer from './visible-form';
import messageReducer from './message';
// Redux: Root Reducer
const rootReducer = combineReducers({
  login: loginReducer,
  visibleForm: visibleFormReducer,
  message: messageReducer,
});

// Exports
export default rootReducer;
