const session = () => {

    let session =  { role : '', id : -1 };
    if (localStorage.getItem("session") !== null) {
        session = JSON.parse(localStorage.getItem("session"))
    }
    return session
};
export   { session };
