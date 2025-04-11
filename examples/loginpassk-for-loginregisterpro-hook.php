<?php namespace ProcessWire;


// LoginPassKey with LoginRegisterPro - add button & script to login FE page
// LoginPassKey with LoginRegisterPro - add button & script to login FE page
if($modules->isInstalled('LoginRegisterPro')){
    $wire->addHookAfter('LoginRegisterProLogin::build', function ($event) {

        $modules = $this->wire('modules');
        $modConfig = $modules->getConfig('LoginPassKey');
        $apiUrl = $modConfig['api_url'];
        $redirectUrl = $modConfig['redirect_url'];

        $form = $event->return;

        // create and add the button
        $passkeyButton = wire('modules')->get('InputfieldButton');
        $passkeyButton->attr('id+name', 'lpk');
        $passkeyButton->addClass('top_button');
        $passkeyButton->attr('value', $this->_("Login with PassKey"));
        $passkeyButton->icon = 'key';
        $passkeyButton->attr('href', '#');
        $form->add($passkeyButton);

        $markUp = $modules->get('InputfieldMarkup');
        $markUp->attr('id+name', 'end');

        // container to display messages. Add classes, styles as you wish
        $html = "<div id='end' class='uk-margin'></div>";

        // get the api url from the LPK configuration
        $html .= "<script>";
        $html .= "let apiUrl = '$apiUrl'\n";
        $html .= "let redirectUrl = '$redirectUrl'\n";
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
                        console.log(res)
                        if(res && res.errno && res.errno === 101) {
                            //end.textContent = res.msg
                            window.location.href = '${redirectUrl}'
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
        $modConfig = wire('modules')->getConfig('LoginPassKey');
        $apiUrl = $modConfig['api_url'];

        if ($user->isLoggedIn() && $lpk->enabled === 1 && $page->template->name !== 'admin') {
            // auto trigger the registration process
            $fwd = new \stdClass();
            $fwd->fn = 'finduser';
            $fwd->un = $user->name;

            $data = new \stdClass();
            $data->pk = $lpk->preRegisterUser($user);
            $data->next = 'register';

            $fwd->data = $data;
            $fwdJSON = json_encode($fwd);

            $js  = "<script>";
            $js .= "let apiUrl = '$apiUrl'\n";
            $js .= "lpk.registerOnly($apiUrl, $fwdJSON)\n";
            $js .= "</script>";
            $return = str_ireplace("</body>", $js . "</body>", $event->return);

            // regardless of outcome, only run once per session
            $session->setFor('lpk', 'success', 'success');
            $event->return = $return;
        }
    });

}