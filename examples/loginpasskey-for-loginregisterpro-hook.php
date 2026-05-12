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

    // Add self-management to the LoginRegisterPro profile form.
    $wire->addHookAfter('LoginRegisterProProfile::build', function ($event) {
        $modules = wire('modules');
        $user = wire('user');
        $lpk = $modules->get('LoginPassKey');

        if(!$lpk->canSelfManagePasskeys($user)) return;

        $form = $event->return;
        $markup = $modules->get('InputfieldMarkup');
        $markup->attr('id+name', 'lpk_profile_passkeys');
        $markup->value = $lpk->renderSelfManagePasskeys($user, 'profile_submit', 'lpk_profile_delete');

        $submit = $form->get('profile_submit');
        if($submit) {
            $form->insertBefore($markup, $submit);
        } else {
            $form->add($markup);
        }

        $event->return = $form;
    });

    // Process LoginRegisterPro profile passkey deletions before the profile save.
    $wire->addHookBefore('LoginRegisterProProfile::process', function ($event) {
        $input = wire('input');
        if($input->post('profile_submit') !== 'lpk_profile_delete') return;

        $session = wire('session');
        $user = wire('user');
        $lpk = wire('modules')->get('LoginPassKey');

        if(!$lpk->canSelfManagePasskeys($user)) {
            throw new WirePermissionException($lpk->_('You do not have permission to manage passkeys.'));
        }

        if(!$session->CSRF->hasValidToken()) {
            throw new WireException($lpk->_('Invalid form submission.'));
        }

        $ids = \array_values(\array_filter(\array_map('intval', (array) $input->post('lpk_profile_deletes'))));
        if(\count($ids) > 0) {
            $count = $lpk->deleteUserPasskeys($user, $ids);
            if($count > 0) {
                $lpk->message(sprintf(_n('%d PassKey deleted from database.', '%d PassKeys deleted from database.', $count), $count));
            }
        }

        $session->redirect('./');
    });

    // Offer passkey registration to an eligible logged-in front end user.
    $wire->addHookAfter('Page::render', function ($event) {
        $session = $this->wire('session');
        if(!empty($session->getFor('lpk', 'success'))) return;

        $user = $this->wire('user');
        $page = $this->wire('page');
        $lpk = $this->wire('modules')->get('LoginPassKey');
        $apiUrl = $lpk->api_url;

        if ($user->isLoggedIn() && $lpk->enabled === 1 && $page->template->name !== 'admin' && !$lpk->userHasPasskey($user)) {
            $fwd = new \stdClass();
            $fwd->fn = 'finduser';
            $fwd->un = $user->name;

            $data = new \stdClass();
            $data->pk = $lpk->preRegisterUser($user);
            $data->next = 'register';

            $fwd->data = $data;
            $fwdJSON = \json_encode($fwd);
            $bannerText = $lpk->_('Add a passkey to sign in faster next time.');
            $addText = $lpk->_('Add passkey');
            $dismissText = $lpk->_('Not now');
            $successText = $lpk->_('Passkey added.');

            $html = <<<HTML
<div id="LoginPassKeyRegisterBanner" class="LoginPassKeyBanner">
    <div>
        <strong>$bannerText</strong>
        <div id="LoginPassKeyRegisterResult" class="LoginPassKeyBannerResult"></div>
    </div>
    <div class="LoginPassKeyBannerActions">
        <button type="button" id="LoginPassKeyRegisterButton">$addText</button>
        <button type="button" id="LoginPassKeyDismissButton">$dismissText</button>
    </div>
</div>
<script>
(() => {
    const banner = document.getElementById('LoginPassKeyRegisterBanner');
    const result = document.getElementById('LoginPassKeyRegisterResult');
    const add = document.getElementById('LoginPassKeyRegisterButton');
    const dismiss = document.getElementById('LoginPassKeyDismissButton');
    const fwd = $fwdJSON;

    if(dismiss && banner) {
        dismiss.addEventListener('click', () => banner.remove());
    }
    if(add && banner) {
        add.addEventListener('click', (event) => {
            event.preventDefault();
            lpk.action('$apiUrl' + 'register', fwd).then((res) => {
                if(res && res.msg && result) result.textContent = res.msg;
                if(res && (res.errno === 102 || res.errno === 101)) {
                    if(result) result.textContent = res.msg || '$successText';
                    add.disabled = true;
                    banner.classList.add('is-success');
                    window.setTimeout(() => banner.classList.add('is-fading'), 1400);
                    window.setTimeout(() => banner.remove(), 1800);
                }
            });
        });
    }
})();
</script>
HTML;
            $return = $event->return;
            foreach (['<main', '<body'] as $target) {
                $pos = stripos($return, $target);
                if ($pos === false) continue;
                $close = strpos($return, '>', $pos);
                if ($close === false) continue;
                $return = substr($return, 0, $close + 1) . $html . substr($return, $close + 1);
                break;
            }
            if (strpos($return, 'LoginPassKeyRegisterBanner') === false) {
                $return = str_ireplace("</body>", $html . "</body>", $return);
            }

            $event->return = $return;
        }
    });
}
