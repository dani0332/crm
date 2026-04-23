<?php

namespace App\Enums;

enum UaePassAPILogLabel: string
{
    case AUTHENTICATION_API = 'Authentication API';
    case TOKEN_API = 'Token API';
    case USER_INFO_API = 'User Info API';
    case GET_SIGNING_ACCESS_TOKEN_API = 'Get Signing AccessToken API';
    case CREATE_SIGN_PROCESS_API = 'Create Sign Process API';
    case GET_SIGNATURE_STATUS = 'Get Signature Status';
    case FETCH_SIGNED_DOCUMENT = 'Fetch Signed Document';
    case DELETE_SIGN_PROCESS = 'Delete SignProcess';
}
