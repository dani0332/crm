// Initial State

import { session } from './session';
import { calculatePermissionAccess } from './permission';
import { config } from './config';
import { setLocalStorage, getLocalStorage } from './localstorage';
import { capitalizeFirstLetter } from './helper';

export {
  session,
  calculatePermissionAccess,
  config,
  getLocalStorage,
  setLocalStorage,
  capitalizeFirstLetter
};
