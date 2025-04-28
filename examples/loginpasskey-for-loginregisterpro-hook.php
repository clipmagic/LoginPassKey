<?php namespace ProcessWire;

// LoginPassKey with LoginRegisterPro
if($modules->isInstalled('LoginRegisterPro') && $modules->isInstalled('LoginPassKey')) {

    if($modules->get('LoginPassKey')->enabled !== 1) return;

    // Add button & script to login FE form
    $wire->addHookAfter('LoginRegisterProLogin::build', function ($event) {

        $modules = wire('modules');
        $lpk = $modules->get('LoginPassKey');
        $page = wire('page');

        // ensure LPK is enabled for the frontend
        if(!$lpk->enabled === 1) return;

        $apiUrl = $lpk->api_url;
//        $redirectUrl = !empty($lpk->redirect_url) ? $page->lpkGetRedirectUrl() : $page->url;

        $form = $event->return;

        // create and add the button
        $passkeyButton = wire('modules')->get('InputfieldButton');
        $passkeyButton->attr('id+name', 'lpk');
        $passkeyButton->addClass('top_button');
        $passkeyButton->attr('value', $lpk->_("Login with PassKey"));
        $passkeyButton->icon = 'key';
        $passkeyButton->attr('href', '#');

        $pwdFld = $form->get('login_pass');
        $form->insertBefore($passkeyButton, $pwdFld);

        $markUp = $modules->get('InputfieldMarkup');
        $markUp->attr('id+name', 'end');

        // container to display messages. Add classes, styles as you wish
        $html = "<div id='end' class='uk-margin'></div>";

        // get the api url from the LPK configuration
        $html .= "<script>";
        $html .= "let apiUrl = '$apiUrl'\n";
        $html .= "</script>";


        // use this default script or replace with your own
        $pageJS = <<<EOT
        <script>
            // hacky solution for iOS not always honouring DOMContentLoaded
            function runOnStart() {
                const btn = document.getElementById('lpk')
                let end = document.getElementById('end')
        
                btn.addEventListener('click', (e) => {
                    e.preventDefault()
                    lpk.action(`${apiUrl}start`).then (res => {
                        if(res && res.errno && res.errno === 101 && res.goto) {
                            window.location.href = res.goto
                        }
                        if(res && res.errno && res.msg) {
                            end.textContent = res.msg
                        }
                    })
                })
            }
        
            if(document.readyState !== 'loading') {
                runOnStart();
            } else {
                document.addEventListener('DOMContentLoaded', function () {
                    runOnStart()
                });
            }
        </script>
    EOT;
        $html .= $pageJS;
        $markUp->value = $html;
        $form->add($markUp);
        $event->return = $form;
    });

    // Register a logged in front end user
    $wire->addHookAfter('Page::render', function ($event) {
        $session = $this->wire('session');
        if(!empty($session->getFor('lpk', 'success'))) return;

        $user = $this->wire('user');
        $page = $this->wire('page');
        $lpk = $this->wire('modules')->get('LoginPassKey');
        $apiUrl = $lpk->api_url;

        if ($user->isLoggedIn() && $lpk->enabled === 1 && $page->template->name !== 'admin') {
            // auto trigger the registration process
            $fwd = new \stdClass();
            $fwd->fn = 'finduser';
            $fwd->un = $user->name;

            $data = new \stdClass();
            $data->pk = $lpk->preRegisterUser($user);
            $data->next = 'register';

            $fwd->data = $data;
            $fwdJSON = \json_encode($fwd);

            $js  = "<script>";
            $js .= "let apiUrl = '$apiUrl'\n";
//            $js .= "lpk.registerOnly('$apiUrl', $fwdJSON)\n";
            $js .= "lpk.action('$apiUrl' + 'register', $fwdJSON)\n";
            $js .= "</script>";
            $return = str_ireplace("</body>", $js . "</body>", $event->return);

            // regardless of outcome, only run once per session
            $session->setFor('lpk', 'success', 'success');
            $event->return = $return;
        }
    });

    // Add some transitions (could have put it in above hook but cleaner
    // to keep them separate. Better still, move the styles & script to
    // your own files
    $wire->addHookAfter('Page::render', function ($event) {
        $css = <<<EOT
                <style>                    
                    .LoginRegisterPro .LoginForm .Inputfields {
                        position: relative;
                        display: grid;
                        grid-template-rows: auto auto 0fr 0fr 0fr;
                        transition: grid-template-rows 1s;
                    }                    
                    .LoginRegisterPro .LoginForm .Inputfields > * {
                        overflow: hidden;
                        padding: 0 !important;
                    }                    
                    .LoginRegisterPro .LoginForm .Inputfields.pword {
                        grid-template-rows: auto 0fr auto auto auto;
                    }                    
                </style>
EOT;
        $js = <<<EOT
<script>
     let btn = document.getElementById('lpk')
     if(btn) {
         btn.addEventListener('click', (e) => {
             let nameFld = document.querySelector('.LoginRegisterPro .LoginForm #login_name')
             if(nameFld.value === '') {
               document.getElementById('Inputfield_login_submit').click();
               return false;
             }
             document.querySelector('.LoginRegisterPro .LoginForm .Inputfields').classList.toggle('pword');
         })
     }
</script>
EOT;
        $return = str_ireplace("</head>", $css, $event->return);
        $event->return = str_ireplace("</body>", $js, $return);
    });
}
