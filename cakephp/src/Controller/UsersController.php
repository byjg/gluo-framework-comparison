<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * Login and logout. The plugin's Form authenticator checks the credentials; these actions
 * only show the form and redirect.
 */
class UsersController extends AppController
{
    /**
     * The login form is the one page a guest can open.
     *
     * @param \Cake\Event\EventInterface $event The event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['login']);
    }

    /**
     * Show the login form, and redirect once the user is identified.
     *
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);
        $result = $this->Authentication->getResult();
        if ($result?->isValid()) {
            return $this->redirect($this->Authentication->getLoginRedirect() ?? '/products');
        }
        if ($this->request->is('post')) {
            $this->Flash->error(__('Invalid e-mail or password.'));
        }

        return null;
    }

    /**
     * Log out and go back to the login form.
     *
     * @return \Cake\Http\Response|null
     */
    public function logout(): ?Response
    {
        $this->Authentication->logout();

        return $this->redirect(['action' => 'login']);
    }
}
