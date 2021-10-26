// Initial State
const initialState = {
  isLogin: 'loading',
};
const loginReducer = (state = initialState, action) => {
  switch (action.type) {
    case 'Login': {
      return {
        ...state,
        isLogin: 'login',
      };
    }
    case 'Signout': {
      return {
        ...state,
        isLogin: 'signout',
      };
    }
    default: {
      return state;
    }
  }
};
export default loginReducer;
