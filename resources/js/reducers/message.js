// Initial State
const initialState = null;
const messageReducer = (state = initialState, action) => {
  switch (action.type) {
    case 'MessageShow': {
      return action.obj;
    }
    default: {
      return state;
    }
  }
};
export default messageReducer;
