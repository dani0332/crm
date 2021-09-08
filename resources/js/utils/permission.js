

import { session } from "./index";
const calculatePermissionAccess = (access) => {

    const { role, id } = session()
    let permission = { read: true, update: true, write: true, delete: true }
    if( access?.read.includes(role)  === false)
        permission.read  = false
    if( access?.update.includes(role)  === false)
        permission.update  = false
    if( access?.write.includes(role)  === false)
        permission.write  = false
    if( access?.delete.includes(role)  === false)
        permission.delete  = false

    return permission
};

export  { calculatePermissionAccess }



