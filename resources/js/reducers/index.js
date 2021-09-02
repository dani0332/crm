// Imports: Dependencies
import { combineReducers } from 'redux';
import loginReducer from './login'
import visibleFormReducer from './visible-form';

// Redux: Root Reducer
const rootReducer = combineReducers({
  login: loginReducer,
  visibleForm: visibleFormReducer,
});

// Exports
export default rootReducer;
