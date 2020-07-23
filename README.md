![CakePHP 3 Notifications Plugin](https://raw.githubusercontent.com/scherersoftware/cake-frontend-bridge/master/cake-frontend-bridge.png)
[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)

A CakePHP 3.8 plugin that allows the creation of JavaScript Controllers for Controller-actions. Code-generation via the shell is possible as well.

## Dependencies

- [Twitter Bootstrap 3.x](http://getbootstrap.com/)
- [jQuery](https://jquery.com/)

## Installation

#### 1. require the plugin

    $ composer require codekanzlei/

#### 2. Load the plugin in your `src/Application.php`

    $this->addPlugin('FrontendBridge');

#### 3. Create additional files

Create the following files and set them up using the given code.

- `app_controller.js`

    **path:**`/webroot/js/app/`

    **template code:**

    ```
    Frontend.AppController = Frontend.Controller.extend({
        baseComponents: [],
        _initialize: function() {
            this.startup();
        }
    });
    ```

#### 4. Configure `AppController.php`

In your `src/Controller/AppController.php`, insert the following pieces of code to setup the Frontend Bridge plugin.

**Traits:**

```
use \FrontendBridge\Lib\FrontendBridgeTrait;
```

**$helpers:**

```
'FrontendBridge' => ['className' => 'FrontendBridge.FrontendBridge'],
```

**$components:**

```
'FrontendBridge.FrontendBridge',
```

**render method**

Overwrite the `render()` method to properly render JsonAction responses.

```
    /**
     * {@inheritDoc}
     */
    public function render($view = null, $layout = null)
    {
        if ($this->request->is('jsonAction')) {
            return $this->renderJsonAction($view, $layout);
        }

        return parent::render($view, $layout);
    }
```

**csrf protection**
If your application uses csrf protection and you are using a different CSRF-Cookie field name than `_csrfToken` you need to configure it.

Inside the `initialize()` method write:

```
$this->components['FrontendBridge.FrontendBridge']['csrfCookieFieldName'] = <YOUR_COOKIE_FIELD_NAME>;
```

#### 5. Load the scripts

Inside your ```<head>``` section add the following code to load all needed .js controllers:

```
<?php if(isset($this->FrontendBridge)) {
    $this->FrontendBridge->init($frontendData);
    echo $this->FrontendBridge->run();
} ?>
```

In development add ```$this->FrontendBridge->addAllControllers();``` into the if block to load without exceptions all .js controllers

#### 6. Add Main Content Classes

Inside your `Layout\default.ctp` add the following line. Inside the div should be your content.

```
<div <?= $this->FrontendBridge->getControllerAttributes(['some', 'optional', 'css', 'classes']) ?>>
    ...
    <?= $this->fetch('content') ?>
    ...
</div>
```

Also add this to other layout files, if you're using them.

## Bake JS Controllers

You can bake JS Controllers for your controller actions by using the bake shell. Just pass the controller name and the camelized action name.

```
bin/cake bake js_controller Posts addPost
```

This command will create the JavaScript-controller file and if necessary the directory structure for it.

You can also bake JS controllers into your plugins by using the `--plugin` option.

## Code Examples

Use the following example code snippets to get a better understanding of the FrontendBridge Plugin and its useful features. Note that you might need to include further Plugins for these.

### 1.) Access view variables in js_controller

Use the following basic setup to pass data from the Backend to the Frontend via FrontendBridge.

- **YourController.php** (CakeController)

    ```
    action() {
        $this->FrontendBridge->setJson(
            'varName',
            'this is sample text');
    }
    ```

- **action.ctp** (Cake View file)

    ```
    display content of json var
    <span class="findMe"></span>
    ```

- **action\_controller.js** (FrontendBridge JS-Controller)

    ```
    startup: function() {
        var varName = this.getVar('varName');
        $('findMe').text(varName);
    }
    ```

Be sure to play close attention to the naming convention, especially regarding the `action_controller.js`. Following the example shown above, its exact path should be `/webroot/js/app/controllers/your/action_controller.js`

The data you passed from the Controller to the Frontend in the variable `varName` should now be rendered in the view.

### 2.) Use modal dialog windows for editing forms

This example uses the following additional Plugins:

- [friendsofcake bootstrap UI](https://github.com/FriendsOfCake/bootstrap-ui) recommended for conveniently creating Form-Elements
- [scherersoftware cake-cktools](https://github.com/scherersoftware/cake-cktools) recommended for powerful buttons

Use the following basic setup to use a modal view element as an edit form that passes the change-request data to the Backend via FrontendBridge. For this example, we will use a basic Users-Model as well as a modal form-element, embedded in the regular view page for a User-Entity to edit and save some of its attributes.

- **UsersController.php** (CakeController)

    ```
    public function editModal ($id = null) {
        $user = $this->Users->get($id);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->data);
            if ($this->Users->save($user)) {
                // prevent redirecting or reloading of the current page
                $this->FrontendBridge->setJson('success', true);
            }
        }
        $this->set(compact('user'));
    }
    ```

- **view.ctp** (regular Cake View file; edit_modal.ctp in front of it)

    ```
    // CkTools button
    <?=$this->CkTools->button('edit_label', [
        'controller' => 'Users',
        'action' => 'editModal',
        $user->id
        ],
        // block interaction with the view file 'behind' the modal element
        ['additionalClasses' => 'modal-form']
    )?>
    ```

- **edit\_modal.ctp** (View file which will be the content of the modal form)

    ```
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
            <?= $this->Form->create($user, [
                'class' => 'dialog-ajax-form dialog-ajax-form-close-on-success',
                'novalidate',
            ]);?>
                <fieldset>
                    <?= $this->Form->input('name'); ?>
                    // [further input fields]
                </fieldset>

                <div class="modal-footer">
                    // FormEnd (save) button generated using CkTools
                    <?= $this->CkTools->formButtons(['cancelButton' => false]); ?>
                </div>
            <?= $this->Form->end(); ?>
        </div>
    </div>
    ```

- **edit\_modal\_controller.js** (FrontendBridge JS-Controller)

    ```
    startup: function() {
        //  add an EventListener to element with class 'modal-form'
        this.$('.modal-form').on('click', function(e) {
            // callback function when Listener is triggered
            e.preventDefault();
            App.Main.Dialog.loadDialog($(e.currentTarget).attr('href'), {
                modalTitle: 'Modal title',
                preventHistory: false
            });
        });
    }
    ```

### 3.) Generate custom AJAX requests

Use the following setup to generate custom AJAX requests. Using FrontendBridge's 'request' action, we will pass JSON data from the Backend to the Frontend.

- **YourController.php** (CakeController)

    ```
    public function getJsonData()
    {
        $code = ApiReturnCode::SUCCESS;
        return $this->Api->response($code, [
            'foo' => 'bar',
            'more' => 'json-data'
        ]); // JSON data which will be logged in your browser console
    }
   ```

- **index.ctp** (Cake View file)

    ```
    <a class="ajax-json-demo">Get JSON data</a>
    ```

- **index\_controller.js** (FrontendBridge JS-Controller)

    ```
    App.Controllers.YourIndexController = Frontend.AppController.extend({
        startup: function() {
            this.$('.ajax-json-demo').click(function() {
                var url = {
                    controller: 'Your',
                    action: 'getJsonData'
                };
                // if postData is null, a GET request will be made
                var postData = null;
                App.Main.request(url, postData, function (response) {
                    alert('Response Code ' + response.code + ' - see console for details');
                    console.log(response.data);
                });
            }.bind(this));
        }
    });
    ```



### 4.) Load content dynamically using AJAX

Use the following setup to make custom HTML requests via AJAX. Using FrontendBridge's  `loadJsonAction` and `request`,  we will implement a very basic livesearch.

This example assumes a Table called `Users` with a field `username` in your database.

- **SearchController.php** (CakeController)

    ```
    public function index() {
        // renders index.ctp
    }

    public function search() {
        $users = null;
        if ($this->request->is(['patch', 'post', 'put']) && !empty($this->request->data['search'])) {
            // search UsersTable for entities that contain
            // request->data['search'] in field 'username'
            $users = TableRegistry::get('Users')->find()
                ->where(['username LIKE' => '%' . $this->request->data['search'] . '%'])
                ->all();
        }
        $this->set(compact('users'));
    }
    ```

- **index.ctp** (Cake View file)

    ```
    <!-- input field -->
    <div class="input-group">
        <input type="text" class="form-control" id="search">
    </div>
    <br />
    <!-- search results (search.ctp) will be rendered here -->
    <div class="results"></div>
    ```

- **search.ctp** (Cake View file)

    ```
    <?php if(!empty($users)) : ?>
        <?php foreach($users as $user) : ?>
            <?= h($user->username) ?><br />
        <?php endforeach; ?>
    <?php else : ?>
        no results to display
    <?php endif; ?>
    ```

- **index\_controller.js** (FrontendBridge JS-Controller)

    ```
    App.Controllers.SearchIndexController = Frontend.AppController.extend({
        startup: function() {
            // set KeyUp-EventListener to field with id 'search' in index.ctp
            this.$('#search').keyup(this._search.bind(this));
        },

        // called at eatch keyup-event in 'search'
        _search: function() {
            var $renderTarget = this.$('.results');
            var url = {
                controller: 'Search',
                action: 'search'
            }

            // create a custom AJAX request with the user input included in the post-data
            App.Main.loadJsonAction(url, {
                data: {
                    search: this.$('#search').val()
                },
                target: $renderTarget, // render the HTML into this selector
            });
        }
    });
    ```

## Functionality Overview

- **loadJsonAction**

    Allows dynamically loading content in the view using AJAX according to user interaction. Uses `request`.

- **request**

    Allows generating custom AJAX requests.
    Also supports FormData (except IE <= 9) as request data.
