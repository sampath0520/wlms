<?php

namespace App\Constants;

class AppConstants
{

    const WEBSITE_LINK = "http://127.0.0.1:8000/";
    const ORIENTATION_EMAIL = "sampath.wijesinghe@archnix.com";

    //user roles
    const ADMIN_ROLE = 'admin';
    const STUDENT_ROLE = 'student';
    const ADMIN_ROLE_ID = 1;
    const STUDENT_ROLE_ID = 2;


    //user statuses
    const USER_ACTIVE = 1;
    const USER_INACTIVE = 0;

    // common statues
    const ACTIVE = 1;
    const INACTIVE = 0;

    //payment method
    const CARD = 1;
    const PAYPAL = 2;

    // payment status
    const PAYMENT_ON_HOLD = 'ON_HOLD';
    const PAYMENT_PENDING = 'PENDING';
    const PAYMENT_PROCESSING = 'PROCESSING';
    const PAYMENT_COMPLETED = 'COMPLETED';
    const PAYMENT_FAILED = 'FAILED';
    const PAYMENT_AUTHENTICATION_REQ = 'AUTHENTICATION_REQUIRED';

    //gender
    const MALE = 1;
    const FEMALE = 2;
    const NO_GENDER_DETAILS = 0;

    //currency
    const CANADIAN_DOLLAR = 'CAD';

    //payment type
    const MANUAL_PAYMENT = 1;
    const CARD_PAYMENT = 0;
    const FREE_COURSE = 2;

    const PERCENTAGE = 1;

    //pagination
    const PAGINATION_LIMIT = 10;

    //video status
    const VIDEO_COMPLETED = 1;
    const VIDEO_INCOMPLETE = 0;

    //Zoom API Credentials
    // const ZOOM_CLIENT_ID = 'kIl0bJipRUKPDcV5vzld6A';
    // const ZOOM_CLIENT_SECRET = '4k7I6tsie4t2RZX7v22vGgwYXJjNkc1c';
    // const ZOOM_REDIRECT_URI = 'https://api.wlms.archnix.dev/api/webinar/callback';

    // const ZOOM_CLIENT_ID = 'PximZiyIRmCzgrZJLNqsMA';
    // const ZOOM_CLIENT_SECRET = 'UubyJUAmm69N1xezUoVRxP5cHUJozDnT';
    // const ZOOM_REDIRECT_URI = 'https://apiv2.wlms.archnix.dev/api/webinar/callback';

    // const ZOOM_CLIENT_ID = 'CIcVFABtTPCJUQMpF7v4w';
    // const ZOOM_CLIENT_SECRET = 'PZQskSpCrzC70QA3Wqx4rvYBRaM5oEbm';
    // const ZOOM_REDIRECT_URI = 'https://apiv2.wlms.archnix.dev/api/webinar/callback';

    const ZOOM_CLIENT_ID = 'PximZiyIRmCzgrZJLNqsMA';
    const ZOOM_CLIENT_SECRET = 'UubyJUAmm69N1xezUoVRxP5cHUJozDnT';
    const ZOOM_REDIRECT_URI = 'https://api.wlms.archnix.dev/api/webinar/callback';

    //Maximum number of devices that can be registered
    const MAXIMUM_NUMBER_OF_DEVICES = 1;

    //course types
    const COURSE_TYPE_ALL = 0;

    //Question types
    const QUESTION_TYPE_RADIO = 1;
    const QUESTION_TYPE_CHECKBOX = 2;

    //zoom meeting success url
    const ZOOM_MEETING_REDIRECT_URL = 'https://staging.wlms.archnix.dev/admin-webinar-create';
    // const ZOOM_MEETING_REDIRECT_URL = 'http://localhost:3000/admin-webinar-create';

}
