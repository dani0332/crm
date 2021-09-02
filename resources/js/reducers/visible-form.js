// Initial State
const initialState = {
};
const visibleFormReducer = (state = initialState, action) => {


    switch (action.type) {
        case 'read':
        case 'edit':
        case 'list':
        case 'new':
        {
            return {
                ...action.obj
            };
        }
        case 'reset':{
            return { }
        }
        default: {
            return state;
        }
    }
};
export default visibleFormReducer;
