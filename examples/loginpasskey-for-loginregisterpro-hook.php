<?php namespace ProcessWire;

// LoginPassKey with LoginRegisterPro
if($modules->isInstalled('LoginRegisterPro') && $modules->isInstalled('LoginPassKey')) {

    if($modules->get('LoginPassKey')->enabled !== 1) return;

    // Add button & script to login FE form
    $wire->addHookAfter('LoginRegisterProLogin::build', function ($event) {

        $modules = wire('modules');
        $lpk = $modules->get('LoginPassKey');

        // ensure LPK is enabled for the frontend
        if($lpk->enabled !== 1) return;

        $apiUrl = $lpk->api_url;

        $form = $event->return;

        // create and add the button
        $passkeyButton = wire('modules')->get('InputfieldButton');
        $passkeyButton->attr('id+name', 'lpk');
        $passkeyButton->addClass('top_button');
        $passkeyButton->attr('value', $lpk->_("Login with PassKey"));
        $passkeyButton->icon = 'key';
        $passkeyButton->attr('href', '#');

        $discoverButton = wire('modules')->get('InputfieldButton');
        $discoverButton->attr('id+name', 'lpk_discover');
        $discoverButton->addClass('top_button');
        $discoverButton->attr('value', $lpk->_("Login with PassKey only"));
        $discoverButton->icon = 'key';
        $discoverButton->attr('href', '#');

        $pwdFld = $form->get('login_pass');
        $form->insertBefore($passkeyButton, $pwdFld);
        $form->insertBefore($discoverButton, $pwdFld);

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
                const discoverBtn = document.getElementById('lpk_discover')
                let end = document.getElementById('end')

                const handleResult = (res) => {
                    if(res && res.errno && res.errno === 101 && res.goto) {
                        window.location.href = res.goto
                    }
                    if(res && res.errno && res.msg) {
                        end.textContent = res.msg
                    }
                }
        
                if(btn) {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault()
                        lpk.action(apiUrl + 'start').then(handleResult)
                    })
                }

                if(discoverBtn) {
                    discoverBtn.addEventListener('click', (e) => {
                        e.preventDefault()
                        lpk.discover(apiUrl).then(handleResult)
                    })
                }
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
            $js .= "lpk.action('$apiUrl' + 'register', $fwdJSON)\n";
            $js .= "</script>";
            $return = str_ireplace("</body>", $js . "</body>", $event->return);

            $event->return = $return;
        }
    });
}
