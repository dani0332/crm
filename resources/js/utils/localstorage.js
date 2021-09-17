const getLocalStorage = (key) => {
    return (localStorage.getItem(key) === null) ? {} : JSON.parse(localStorage.getItem(key))
};
const setLocalStorage = (key, value) =>  {
    if(localStorage.getItem(key) === null){
        localStorage.setItem(key, JSON.stringify(value))
    }else{
        const getValue = JSON.parse(localStorage.getItem(key))
        localStorage.setItem(key, JSON.stringify({ ...getValue, ...value }))
    }
}
export   { getLocalStorage, setLocalStorage };
