<x-mail::message>
    # Welcome {{ $user->name }}!<br>

    Please you can login using this credentials:<br>
    
    Email: {{ $user->email }}
    
    Password: {{ $temporaryPassword }}
    
    After logging in, we kindly ask you to verify your email address once again to ensure the security of your account.
    

<x-mail::button :url="'http://app.allfliptix.net/'">
Login
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
