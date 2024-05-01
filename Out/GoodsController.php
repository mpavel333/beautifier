<?php
namespace app\controllers;

use Yii;
use yii\web\UploadedFile;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use app\behaviors\RoleBehavior;
use app\models\Goods;
use app\models\GoodsSearch;
use yii\web\Controller;
use app\models\Template;
use app\components\TagsHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;

/**
 * GoodsController implements the CRUD actions for Goods model.
 */
class GoodsController extends Controller {
    /**
     * @inheritdoc
     */
    public function behaviors() {

        return ['verbs' => ['class' => VerbFilter::className() , 'actions' => ['delete' => ['post'], 'bulk-delete' => ['post'], ], ], 'access' => ['class' => \yii\filters\AccessControl::className() , 'except' => ['application-form', 'export-data-xml-avito'], 'rules' => [['allow' => true,

        'roles' => ['@'], ], ], ], 'role' => ['class' => RoleBehavior::class , 'instanceQuery' => \app\models\Goods::find() , 'actions' => ['create' => 'goods_create',

        'update' => 'goods_update', 'view' => 'goods_view', 'delete' => 'goods_delete', 'bulk-delete' => 'goods_delete', 'index' => ['goods_view', 'goods_view_all'], ], ], ];
    }

    /**
     * Lists all Goods models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = new GoodsSearch();
        $onFilters = false;

        if (isset($_GET['GoodsSearch']) == false) {
            $session = \Yii::$app->session;
            if ($session->has('GoodsSearch')) {
                foreach ($session->get('GoodsSearch') as $attr => $value) {
                    $searchModel->$attr = $value;
                    if ($value) {
                        $onFilters = true;
                    }
                }
            }
        }
        else {
            $session = \Yii::$app->session;
            $session->set('GoodsSearch', $_GET['GoodsSearch']);
            $onFilters = true;
        }

        $dataProvider = $searchModel->search(Yii::$app
            ->request
            ->queryParams);

        $role = \app\models\Role::findOne(\Yii::$app
            ->user
            ->identity
            ->role_id);
        if ($role && $role->enabled_good_statuses) {
            // $dataProvider->query->joinWith(['status']);
            $dataProvider
                ->query
                ->andWhere(['status_id' => explode(',', $role->enabled_good_statuses) ]);
        }

        return $this->render('index', ['searchModel' => $searchModel, 'dataProvider' => $dataProvider, 'onFilters' => $onFilters, ]);
    }

    /**
     * Creates a new Order model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionMassiveStatus($pks, $pjax = '#crud-datatable-goods') {
        $request = Yii::$app->request;
        Yii::$app
            ->response->format = Response::FORMAT_JSON;

        if ($request->isGet) {
            return ['title' => \Yii::t('app', "Изменить статус ") , 'content' => $this->renderAjax('status', ['pks' => $pks]) , 'footer' => Html::button(\Yii::t('app', 'Отмена') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Готово') , ['class' => 'btn btn-primary', 'type' => "submit"])

            ];
        }
        elseif ($request->isPost) {

            $pks = isset($_POST['pks']) ? $_POST['pks'] : null;
            $pks = explode(',', $pks);

            $status = isset($_POST['status']) ? $_POST['status'] : null;

            Goods::updateAll(['status_id' => $status], ['id' => $pks]);

            return ['forceClose' => true, 'forceReload' => $pjax, ];
        }
    }

    public function actionMyImport() {
        $request = Yii::$app->request;
        $model = new Goods();

        if ($request->isAjax) {

            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            $model->fileUploading = UploadedFile::getInstance($model, 'fileUploading');
            $error = 0;
            $success = 0;
            if (!empty($model->fileUploading)) {
                $filename = 'uploads/' . $model->fileUploading;
                $model
                    ->fileUploading
                    ->saveAs($filename);
                $file = fopen($filename, 'r');
                if ($file) {
                    $Reader = \PHPExcel_IOFactory::createReaderForFile($filename);
                    $Reader->setReadDataOnly(true); // set this, to not read all excel properties, just data
                    $objXLS = $Reader->load($filename);
                    foreach ($objXLS->getWorksheetIterator() as $worksheet) {
                        $worksheetTitle = $worksheet->getTitle();
                        $highestRow = $worksheet->getHighestRow(); // e.g. 10
                        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
                        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                        $nrColumns = ord($highestColumn) - 64;
                        for ($row = 2;$row <= $highestRow;++$row) {
                            \Yii::warning($row);
                            $cell = $worksheet->getCellByColumnAndRow(0, $row);
                            if (!$cell->getValue()) {
                                continue;
                            }
                            $newModel = new Goods();
                            $cell = $worksheet->getCellByColumnAndRow(0, $row);
                            $newModel->category_id = (string)$cell->getValue();
                            $cell = $worksheet->getCellByColumnAndRow(1, $row);
                            $newModel->brand_id = $cell->getValue();
                            $cell = $worksheet->getCellByColumnAndRow(2, $row);
                            $newModel->name = $cell->getValue();
                            $cell = $worksheet->getCellByColumnAndRow(3, $row);
                            $newModel->fault = $cell->getValue();
                            $cell = $worksheet->getCellByColumnAndRow(4, $row);
                            $newModel->buy_price = $cell->getValue();
                            $cell = $worksheet->getCellByColumnAndRow(5, $row);
                            $newModel->price_condit = $cell->getValue();
                            // $cell = $worksheet->getCellByColumnAndRow(6, $row);
                            // $newModel->price  = $cell->getValue();
                            // $cell = $worksheet->getCellByColumnAndRow(7, $row);
                            // $statusName = $cell->getValue();
                            // $statusModel = \app\models\Status::find()->where(['name' => $statusName])->one();
                            // if($statusModel){
                            //     $newModel->status_id = $statusModel->id;
                            // }
                            $cell = $worksheet->getCellByColumnAndRow(6, $row);
                            $newModel->serial = strval($cell->getValue());

                            // $cell = $worksheet->getCellByColumnAndRow(4, $row);
                            // $newModel->kommentarii  = $cell->getValue();
                            $newModel->status_id = 1;
                            if (!$newModel->save()) {
                                \Yii::warning($newModel->errors, 'error');
                                $error++;
                            }
                            else {
                                $success++;
                            }
                        }
                    }

                    return ['forceReload' => '#crud-datatable-goods', 'title' => "Загружения", 'content' => "Удачно загруженно: {$success} <br/> Ошибка загрузки: {$error}", 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) ];
                    // exit;
                    return ['forceReload' => '#crud-datatable-goods', 'forceClose' => true, ];
                }
                else {
                    return ['forceReload' => '#crud-datatable-goods', 'title' => "Загружения", 'content' => "<span class='text-danger'>Ошибка при загрузке файла</span>", 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) ];
                }
            }
            else {
                return ['title' => "<span class='text-danger'>Выберите файл</span>", 'size' => 'normal', 'content' => $this->renderAjax('_my_import', ['model' => $model, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-primary pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Сохранить') , ['class' => 'btn btn-info', 'type' => "submit"]) ];
            }
        }
    }
    public function actionSessionForm() {
        Yii::$app
            ->session
            ->set('goods-form-session', \yii\helpers\ArrayHelper::getValue($_POST, 'Goods'));
    }

    /**
     * Temp an existing Nomenclature model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionExportDataXmlAvito() {
        $searchModel = new GoodsSearch();
        $dataProvider = $searchModel->search(Yii::$app
            ->request->queryParams, true);
        // $dataProvider->query->andWhere(['id' => '4368']);
        $dataProvider
            ->query
            ->andWhere(['is_avito' => true]);
        $dataProvider->pagination = false;

        $data = [];

        $models = $dataProvider->models;

        $models = array_filter($models, function ($model) {
            $files = json_decode($model->files, true);
            $category = $model->category;

            $filesValidation = is_array($files) && count($files) > 0;
            $categoryValidation = $category->avito_category ? true : false;

            return $filesValidation && $categoryValidation;
        });

        $xmlContent = $this->renderPartial('_export_avito', ['models' => $models, ]);

        $myfile = fopen("data.xml", "w");
        fwrite($myfile, $xmlContent);
        fclose($myfile);

        Yii::$app
            ->response
            ->sendFile('data.xml');
    }

    public function actionUploadFile() {

        Yii::$app
            ->response->format = Response::FORMAT_JSON;
        $fileName = Yii::$app
            ->security
            ->generateRandomString();
        if (is_dir('uploads') == false) {
            mkdir('uploads');
        }
        $uploadPath = 'uploads';
        if (isset($_FILES['file'])) {
            $file = \yii\web\UploadedFile::getInstanceByName('file');
            $path = $uploadPath . '/' . $fileName . '.' . $file->extension;

            $file->saveAs($path);

            \Yii::warning($file->size, '$file->size');
            // $this->compressImage($path, $path, 15);
            $this->compressImage($path, $path, 30);
            // $base = log($file->size, 1024);
            $base = log(filesize($path) , 1024);
            $suffixes = array(
                'Bytes',
                'Kb',
                'Mb',
                'Gb',
                'Tb'
            );
            $size = round(pow(1024, $base - floor($base)) , 2) . ' ' . $suffixes[floor($base) ];

            return ['name' => $file->name, 'url' => '/' . $path, 'size' => $size, ];
        }
    }

    private function compressImage($source_url, $destination_url, $quality) {
        $info = getimagesize($source_url);

        if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($source_url);
        elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($source_url);
        elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($source_url);

        //save file
        imagejpeg($image, $destination_url, $quality);

        //return destination file
        return $destination_url;
    }

    public function actionExistImages() {
        $goods = \app\models\Goods::find()->where(['and', ['is not', 'files', null], ['!=', 'files', '']])
            ->all();

        foreach ($goods as $good) {
            $text = "<h4>#{$good->id}</h4><ul>";
            $files = json_decode($good->files, true);
            if (is_array($files)) {;
                foreach ($files as $file) {
                    if (isset($file['url'])) {
                        if (file_exists(substr($file['url'], 1))) {
                            $text .= "<li>{$file['url']}</li>";
                        }
                        else {
                            $text .= "<li style='color: red;'>{$file['url']}</li>";
                        }
                    }
                }
                $text .= "</ul>";
                echo $text;
                $changed = false;
                $filesChagned = array_values(array_filter($files, function ($file) use (&$changed) {
                    if (isset($file['url'])) {
                        if (file_exists(substr($file['url'], 1))) {
                            return true;
                        }
                        else {
                            $changed = true;
                            return false;
                        }
                    }
                    return false;
                }));
                if ($changed) {
                    $good->files = json_encode($filesChagned, JSON_UNESCAPED_UNICODE);
                    $good->save(false);
                }
            }
            else {
                echo "<p>no files</p>";
            }
        }
    }

    public function actionImageDelete($name, $id = null) {
        $path = substr($name, 1);
        if (is_file($path)) {
            unlink($path);
        }

        if ($id) {
            $model = $this->findModel($id);
            $file = json_decode($model->files, true);
            \Yii::warning($file, '$file');
            $file = array_filter($file, function ($model) use ($name) {
                return $name != $model['url'];
            });
            \Yii::warning($file, '$file');
            $model->files = json_encode($file);
            $model->save(false);
        }
        return null;
    }

    /**
     * Creates a new Objects model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateStatus($myRoute = "goods", $update = null, $id = null) {
        $request = Yii::$app->request;
        if ($update == null) {
            $model = new \app\models\Status();
        }
        else {
            $model = \app\models\Status::findOne($update);
        }
        if ($request->isAjax) {
            /*
             *   Process for ajax request
            */
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                if ($id) {
                    Yii::$app
                        ->session
                        ->set('route-session-update', $id);
                }
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                else {
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                \Yii::warning($r);

                if ($update == null) {
                    $viewLink = '@app/views/status/_form';
                }
                else {
                    $viewLink = '@app/views/status/_form';
                }
                return ['title' => \Yii::t('app', "Добавить Статусы товара") , 'content' => $this->renderAjax($viewLink, ['model' => $model, 'action' => "/goods/create-status", ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::a(\Yii::t('app', 'Назад') , '/goods/create', ['class' => 'btn btn-warning', 'role' => 'modal-remote', ]) . Html::button(\Yii::t('app', 'Добавить') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else if ($model->load($request->post()) && $model->save()) {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }
                }
                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }
                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->status_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Товары") , 'content' => $this->renderAjax($id ? '_form' : '_form', ['model' => $origin, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }
                }
                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }
                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->status_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Статусы товара") , 'content' => $this->renderAjax('@app/views/status/create', ['model' => $model, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
        }
        else {
            /*
             *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
            else {
                return $this->render('@app/views/status/create', ['model' => $model, ]);
            }
        }
    }

    /**
     * Creates a new Objects model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateStatusRepair($myRoute = "goods", $update = null, $id = null) {
        $request = Yii::$app->request;
        if ($update == null) {
            $model = new \app\models\StatusRepair();
        }
        else {
            $model = \app\models\StatusRepair::findOne($update);
        }
        if ($request->isAjax) {
            /*
             *   Process for ajax request
            */
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                if ($id) {
                    Yii::$app
                        ->session
                        ->set('route-session-update', $id);
                }
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                else {
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                \Yii::warning($r);

                if ($update == null) {
                    $viewLink = '@app/views/status-repair/_form';
                }
                else {
                    $viewLink = '@app/views/status-repair/_form';
                }
                return ['title' => \Yii::t('app', "Добавить Статусы товара") , 'content' => $this->renderAjax($viewLink, ['model' => $model, 'action' => "/goods/create-status-repair", ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::a(\Yii::t('app', 'Назад') , '/goods/create', ['class' => 'btn btn-warning', 'role' => 'modal-remote', ]) . Html::button(\Yii::t('app', 'Добавить') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else if ($model->load($request->post()) && $model->save()) {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }
                }
                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }
                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->status_repair_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Товары") , 'content' => $this->renderAjax($id ? '_form' : '_form', ['model' => $origin, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }
                }
                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }
                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->status_repair_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Статусы товара") , 'content' => $this->renderAjax('@app/views/status-repair/create', ['model' => $model, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
        }
        else {
            /*
             *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
            else {
                return $this->render('@app/views/status-repair/create', ['model' => $model, ]);
            }
        }
    }

    /**
     * Creates a new Objects model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateManufacturer($myRoute = "goods", $update = null, $id = null) {
        $request = Yii::$app->request;
        if ($update == null) {
            $model = new \app\models\Manufacturer();
        }
        else {
            $model = \app\models\Manufacturer::findOne($update);
        }
        if ($request->isAjax) {
            /*
             *   Process for ajax request
            */
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                if ($id) {
                    Yii::$app
                        ->session
                        ->set('route-session-update', $id);
                }
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                else {
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                \Yii::warning($r);

                if ($update == null) {
                    $viewLink = '@app/views/manufacturer/_form';
                }
                else {
                    $viewLink = '@app/views/manufacturer/_form';
                }
                return ['title' => \Yii::t('app', "Добавить Категория") , 'content' => $this->renderAjax($viewLink, ['model' => $model, 'action' => "/goods/create-manufacturer", ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::a(\Yii::t('app', 'Назад') , '/goods/create', ['class' => 'btn btn-warning', 'role' => 'modal-remote', ]) . Html::button(\Yii::t('app', 'Добавить') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else if ($model->load($request->post()) && $model->save()) {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }
                }
                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }
                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->category_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Товары") , 'content' => $this->renderAjax($id ? '_form' : '_form', ['model' => $origin, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }
                }
                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }
                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->category_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Категория") , 'content' => $this->renderAjax('@app/views/manufacturer/create', ['model' => $model, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
        }
        else {
            /*
             *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
            else {
                return $this->render('@app/views/manufacturer/create', ['model' => $model, ]);
            }
        }
    }

    public function actionChangeAttribute($id, $attr, $value) {
        $model = $this->findModel($id);

        $model->$attr = $value;

        $model->save(false);
    }

    /**
     * Temp an existing Nomenclature model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionCheckBySerial($id) {
        Yii::$app
            ->response->format = Response::FORMAT_JSON;
        $model = $this->findModel($id);

        if (\Yii::$app
            ->request
            ->isGet) {
            return ['title' => \Yii::t('app', "Серийный номер") , 'content' => $this->renderAjax('check-by-serial', ['error' => false, 'model' => $model, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Готово') , ['class' => 'btn btn-primary', 'type' => "submit"]) ];
        }
        elseif (\Yii::$app
            ->request
            ->isPost) {

            $serial = isset($_POST['serial']) ? $_POST['serial'] : null;

            if ($model->serial === $serial) {
                $order = new \app\models\Order(['tovar_id' => $id, 'status_id' => 2]);

                return ['title' => \Yii::t('app', "Добавить ") , 'content' => $this->renderAjax('@app/views/order/_form', ['model' => $order, 'action' => 'create', 'formUrl' => ['/order/create', 'pjax' => '#crud-datatable-goods-container', 'clouse' => true], ]) , 'footer' => Html::button(\Yii::t('app', 'Отмена') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"]) ];
            }
            else {
                return ['title' => \Yii::t('app', "Серийный номер") , 'content' => $this->renderAjax('check-by-serial', ['error' => true, 'model' => $model, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Готово') , ['class' => 'btn btn-primary', 'type' => "submit"]) ];
            }
        }
    }

    /**
     * Temp an existing Nomenclature model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionExportData() {
        Yii::$app
            ->response->format = Response::FORMAT_JSON;

        if (\Yii::$app
            ->request
            ->isGet) {
            return ['title' => \Yii::t('app', "Добавить Брэнд") , 'content' => $this->renderAjax('export-form', []) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Далее') , ['class' => 'btn btn-primary', 'type' => "submit"]) ];
        }
        elseif (\Yii::$app
            ->request
            ->isPost) {

            $attributes = isset($_POST['attributes']) ? $_POST['attributes'] : [];
            $condit_price_percent = isset($_POST['condit_price_percent']) ? $_POST['condit_price_percent'] : [];
            $attributes = implode(',', array_keys($attributes));

            return ['title' => \Yii::t('app', "Добавить Брэнд") , 'content' => \yii\helpers\Html::a('Скачать Excel', \yii\helpers\ArrayHelper::merge(['goods/export-data-exec', 'attrs' => $attributes, 'condit_price_percent' => $condit_price_percent], Yii::$app
                ->request
                ->queryParams) , ['class' => 'btn btn-success btn-sm btn-block', 'data-pjax' => 0]) , 'footer' => '', ];
        }
    }

    /**
     * Temp an existing Nomenclature model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionExportDataExec($attrs, $condit_price_percent = null) {
        ini_set('memory_limit', '-1');
        $attrs = explode(',', $attrs);

        $searchModel = new GoodsSearch();
        $dataProvider = $searchModel->search(Yii::$app
            ->request->queryParams, true);
        $dataProvider->pagination = false;
        $columns = require ('../views/goods/_export_columns.php');

        $data = [];

        foreach ($dataProvider->models as $model2) {
            $row = [];

            foreach ($columns as $column) {

                $value = null;

                if (isset($column['attribute']) == false) {
                    continue;
                }

                if (count($attrs) > 0 && isset($column['attribute']) && isset($column['attribute']) && in_array($column['attribute'], $attrs) == false) {
                    continue;
                }

                if (isset($column['visible'])) {
                    if ($column['visible'] == false) {
                        continue;
                    }
                }

                if (isset($column['content'])) {
                    $value = call_user_func($column['content'], $model2);
                }
                elseif (isset($column['value'])) {
                    if (is_callable($column['value'])) {
                        $value = call_user_func($column['value'], $model2);
                    }
                    else {
                        $value = \yii\helpers\ArrayHelper::getValue($model2, $column['value']);
                    }
                }
                else {
                    $attr2 = isset($column['attribute']) ? $column['attribute'] : null;
                    $value = isset($attr2) ? $model2[$attr2] : null;
                }
                if ($value != null) {
                    $row[] = $value;
                }
                else {
                    if (isset($column['attribute'])) {
                        $attribute = $column['attribute'];
                        if ($attribute == 'amount') {
                            $row[] = 0;
                        }
                        else {
                            $row[] = null;
                        }
                    }
                    else {
                        $row[] = null;
                    }
                }
            }

            $row[] = $condit_price_percent . '%';

            // Ячейка %
            if ($model2->price_condit) {
                if (is_numeric($condit_price_percent)) {
                    $row[] = $model2->price_condit - round($model2->price_condit / 100 * $condit_price_percent);
                }
                else {
                    $row[] = '—';
                }
            }
            else {
                $row[] = "—";
            }

            $data[] = $row;
        }

        \Yii::warning($data);

        $model = new Goods();

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("creater");
        $objPHPExcel->getProperties()
            ->setLastModifiedBy("Middle field");
        $objPHPExcel->getProperties()
            ->setSubject("Subject");
        $objGet = $objPHPExcel->getActiveSheet();

        $i = 0;
        foreach ($columns as $column) {

            $label = null;

            if (isset($column['attribute']) == false) {
                continue;
            }

            if (count($attrs) > 0 && isset($column['attribute']) && isset($column['attribute']) && in_array($column['attribute'], $attrs) == false) {
                continue;
            }

            if (isset($column['visible'])) {
                if ($column['visible'] == false) {
                    continue;
                }
            }

            if (isset($column['label'])) {
                $label = $column['label'];
            }
            elseif (isset($column['attribute'])) {
                $label = $model->getAttributeLabel($column['attribute']);
            }

            $objGet->setCellValueByColumnAndRow($i, 1, $label);
            $i++;
        }
        $objGet->setCellValueByColumnAndRow($i, 1, "%");
        $i++;
        $objGet->setCellValueByColumnAndRow($i, 1, "ИТОГО:");

        for ($i = 0;$i <= count($data);$i++) {
            if (isset($data[$i]) == false) {
                continue;
            }

            $row = $data[$i];
            \Yii::warning($row);

            for ($j = 0;$j <= count($row);$j++) {
                if (isset($row[$j])) {
                    $value = $row[$j];
                    // $objGet->setCellValueByColumnAndRow($j, ($i + 1), $value);
                    $objGet->setCellValueByColumnAndRow($j, ($i + 2) , strip_tags($value));
                }
            }
        }

        $filename = 'data.xlsx';
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        $objWriter->save('data.xlsx');

        Yii::$app
            ->response
            ->sendFile('data.xlsx');
    }

    /**
     * Temp an existing Nomenclature model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionExportDataXml() {
        $searchModel = new GoodsSearch();
        $dataProvider = $searchModel->search(Yii::$app
            ->request->queryParams, true);
        $dataProvider->pagination = false;

        $data = [];

        $models = $dataProvider->models;

        $xmlContent = $this->renderPartial('_export_xml', ['models' => $models, ]);

        $myfile = fopen("data.xml", "w");
        fwrite($myfile, $xmlContent);
        fclose($myfile);

        Yii::$app
            ->response
            ->sendFile('data.xml');
    }

    /**
     * Creates a new Objects model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreateBrand($myRoute = "goods", $update = null, $id = null) {
        $request = Yii::$app->request;
        if ($update == null) {
            $model = new \app\models\Brand();
        }
        else {
            $model = \app\models\Brand::findOne($update);
        }
        if ($request->isAjax) {
            /*
             *   Process for ajax request
            */
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                if ($id) {
                    Yii::$app
                        ->session
                        ->set('route-session-update', $id);
                }
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                else {
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                \Yii::warning($r);

                if ($update == null) {
                    $viewLink = '@app/views/brand/_form';
                }
                else {
                    $viewLink = '@app/views/brand/_form';
                }
                return ['title' => \Yii::t('app', "Добавить Брэнд") , 'content' => $this->renderAjax($viewLink, ['model' => $model, 'action' => "/goods/create-brand", ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::a(\Yii::t('app', 'Назад') , '/goods/create', ['class' => 'btn btn-warning', 'role' => 'modal-remote', ]) . Html::button(\Yii::t('app', 'Добавить') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else if ($model->load($request->post()) && $model->save()) {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }
                }
                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }
                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->brand_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Товары") , 'content' => $this->renderAjax($id ? '_form' : '_form', ['model' => $origin, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }
                }
                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }
                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->brand_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Брэнд") , 'content' => $this->renderAjax('@app/views/brand/create', ['model' => $model, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
        }
        else {
            /*
             *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
            else {
                return $this->render('@app/views/brand/create', ['model' => $model, ]);
            }
        }
    }
    /**
     * Displays a single Goods model.
     *
     * @return mixed
     */
    public function actionView($id) {
        $request = Yii::$app->request;
        $model = $this->findModel($id);

        $orderSearchModel = new \app\models\OrderSearch();
        $orderDataProvider = $orderSearchModel->search(Yii::$app
            ->request
            ->queryParams);
        $orderDataProvider
            ->query
            ->andWhere(['tovar_id' => $id]);
        $paySearchModel = new \app\models\PaySearch();
        $payDataProvider = $paySearchModel->search(Yii::$app
            ->request
            ->queryParams);
        $payDataProvider
            ->query
            ->andWhere(['tovar_id' => $id]);
        $logsSearchModel = new \app\models\LogsSearch();
        $logsDataProvider = $logsSearchModel->search(Yii::$app
            ->request
            ->queryParams);
        $logsDataProvider
            ->query
            ->andWhere(['good_id' => $id]);

        if ($request->isAjax) {
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            return ['title' => \Yii::t('app', '') . " #" . $id, 'content' => $this->renderAjax('view', ['model' => $model, 'orderSearchModel' => $orderSearchModel, 'orderDataProvider' => $orderDataProvider, 'paySearchModel' => $paySearchModel, 'payDataProvider' => $payDataProvider, 'logsSearchModel' => $logsSearchModel, 'logsDataProvider' => $logsDataProvider, ]) , 'footer' => Html::button(\Yii::t('app', 'Отмена') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::a(\Yii::t('app', 'Изменить') , ['update', 'id' => $model->id], ['class' => 'btn btn-primary', 'role' => 'modal-remote']) ];
        }
        else {
            return $this->render('view', ['model' => $this->findModel($id) , 'orderSearchModel' => $orderSearchModel, 'orderDataProvider' => $orderDataProvider, 'paySearchModel' => $paySearchModel, 'payDataProvider' => $payDataProvider, 'logsSearchModel' => $logsSearchModel, 'logsDataProvider' => $logsDataProvider, ]);
        }
    }

    /**
     * Creates a new Goods model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($pjax = '#crud-datatable-goods', $clouse = false, $atr = null, $value = null) {
        $request = Yii::$app->request;
        $model = new Goods();
        Yii::$app
            ->session
            ->remove('route-session-update');
        if (Yii::$app
            ->session
            ->has('route-session')) {
            Yii::$app
                ->session
                ->remove('route-session');
        }
        if (Yii::$app
            ->session
            ->has('goods-form-session')) {
            $model->attributes = Yii::$app
                ->session
                ->get('goods-form-session');
        }
        if ($request->isGet) {
            $model->load(Yii::$app
                ->request
                ->queryParams);
        }

        if ($request->isAjax) {
            /*
             *   Process for ajax request
            */
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            if ($atr != null) {
                $model->$atr = $value;
            }
            if ($request->isGet) {

                return ['title' => \Yii::t('app', "Добавить ") , 'content' => $this->renderAjax('_form', ['model' => $model, 'action' => 'create', ]) , 'footer' => Html::button(\Yii::t('app', 'Отмена') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else if ($model->load($request->post()) && $model->save()) {
                Yii::$app
                    ->session
                    ->remove('goods-form-session');

                return $this->redirect(['/order']);
                if ($clouse) {
                    return ['forceReload' => $pjax, 'forceClose' => true, ];
                }
                else {
                    return ['forceReload' => $pjax, 'title' => \Yii::t('app', "Добавить ") , 'content' => '<span class="text-success">' . \Yii::t('app', 'Создание  успешно завершено') . '</span>', 'footer' => Html::button(\Yii::t('app', 'ОК') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::a(\Yii::t('app', 'Создать еще') , ['create'], ['class' => 'btn btn-primary', 'role' => 'modal-remote'])

                    ];
                }

            }
            else {

                return ['title' => \Yii::t('app', "Добавить ") , 'content' => $this->renderAjax('_form', ['model' => $model, 'action' => 'create', ]) , 'footer' => Html::button(\Yii::t('app', 'Отмена') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', \Yii::t('app', 'Создать')) , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
        }
        else {
            /*
             *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
            else {
                return $this->render('_form', ['model' => $model, 'action' => 'create', ]);
            }
        }

    }

    /**
     * Updates an existing Goods model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionUpdate($id, $pjax = '#crud-datatable-goods') {
        $request = Yii::$app->request;
        $model = $this->findModel($id);
        Yii::$app
            ->session
            ->remove('route-session-update');
        if (Yii::$app
            ->session
            ->has('route-session')) {
            Yii::$app
                ->session
                ->remove('route-session');
        }
        if ($request->isAjax) {
            /*
             *   Process for ajax request
            */
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {

                return ['title' => \Yii::t('app', "Изменить  #") . $id, 'content' => $this->renderAjax('_form', ['model' => $model, 'action' => 'update', ]) , 'footer' => Html::button(\Yii::t('app', 'Отмена') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Сохранить') , ['class' => 'btn btn-primary', 'type' => "submit"]) ];
            }
            else if ($model->load($request->post()) && $model->save()) {

                return ['forceReload' => $pjax, 'forceClose' => true, ];

            }
            else {

                return ['title' => \Yii::t('app', "Изменить  #") . $id, 'content' => $this->renderAjax('_form', ['model' => $model, 'action' => 'update', ]) , 'footer' => Html::button(\Yii::t('app', 'Отмена') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Сохранить') , ['class' => 'btn btn-primary', 'type' => "submit"]) ];
            }
        }
        else {
            /*
             *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);

            }
            else {
                return $this->render('_form', ['model' => $model, 'action' => 'update', ]);
            }
        }
    }

    public function actionCreateStorage($myRoute = "goods", $update = null, $id = null) {
        $request = Yii::$app->request;
        if ($update == null) {
            $model = new \app\models\Storage();
        }
        else {
            $model = \app\models\Storage::findOne($update);
        }
        if ($request->isAjax) {
            /*
             *   Process for ajax request
            */
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            if ($request->isGet) {
                if ($id) {
                    Yii::$app
                        ->session
                        ->set('route-session-update', $id);
                }
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                else {
                    $r[] = $myRoute;
                    Yii::$app
                        ->session
                        ->set('route-session', $r);
                }
                \Yii::warning($r);

                if ($update == null) {
                    $viewLink = '@app/views/storage/_form';
                }
                else {
                    $viewLink = '@app/views/storage/_form';
                }
                return ['title' => \Yii::t('app', "Добавить Комплектующие") , 'content' => $this->renderAjax($viewLink, ['model' => $model, 'action' => "/goods/create-storage", ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::a(\Yii::t('app', 'Назад') , '/goods/create', ['class' => 'btn btn-warning', 'role' => 'modal-remote', ]) . Html::button(\Yii::t('app', 'Добавить') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
            else if ($model->load($request->post()) && $model->save()) {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }

                }

                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }

                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->storage_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Комплектующие") , 'content' => $this->renderAjax($id ? '_form' : '_form', ['model' => $origin, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"]) ];

            }
            else {
                if (Yii::$app
                    ->session
                    ->has('route-session')) {
                    $r = Yii::$app
                        ->session
                        ->get('route-session');
                    $lastRo = "";
                    $newR = [];
                    foreach ($r as $value) {
                        if ($value != $myRoute) {
                            $newR[] = $value;
                            $lastRo = $value;
                        }
                    }
                    \Yii::warning($newR);
                    if (count($newR) > 0) {
                        Yii::$app
                            ->session
                            ->set('route-session', $newR);
                        $action = "/{$lastRo}/create-{$myRoute}";
                    }
                    else {
                        if (Yii::$app
                            ->session
                            ->has('route-session-update')) {
                            $id = Yii::$app
                                ->session
                                ->get('route-session-update');
                        }
                        $action = $id ? "/{$myRoute}/update?id={$id}" : "/{$myRoute}/create";
                    }
                }
                if ($id) {
                    $origin = $this->findModel($id);
                }
                else {
                    $origin = (new Goods());
                }
                \Yii::warning($action);

                if (Yii::$app
                    ->session
                    ->has('goods-form-session')) {
                    $origin->attributes = Yii::$app
                        ->session
                        ->get('goods-form-session');
                }
                $origin->storage_id = $model->id;

                return ['title' => \Yii::t('app', "Добавить Комплектующие") , 'content' => $this->renderAjax('@app/views/storage/create', ['model' => $model, 'action' => $action, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Создать') , ['class' => 'btn btn-primary', 'type' => "submit"])

                ];
            }
        }
        else {
            /*
             *   Process for non-ajax request
            */

            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
            else {
                return $this->render('@app/views/storage/create', ['model' => $model, ]);
            }
        }
    }

    public function actionAdd() {
        $request = Yii::$app->request;
        $model = new Goods();

        if ($request->isAjax) {

            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            $model->fileUploading = UploadedFile::getInstance($model, 'fileUploading');
            $error = 0;
            $success = 0;
            if (!empty($model->fileUploading)) {
                $filename = 'uploads/' . $model->fileUploading;
                $model
                    ->fileUploading
                    ->saveAs($filename);
                $file = fopen($filename, 'r');
                if ($file) {
                    $objPHPExcel = PHPExcel_IOFactory::load($filename);
                    foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
                        $worksheetTitle = $worksheet->getTitle();
                        $highestRow = $worksheet->getHighestRow(); // e.g. 10
                        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
                        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                        $nrColumns = ord($highestColumn) - 64;
                        for ($row = 2;$row <= $highestRow;++$row) {

                            $cell = $worksheet->getCellByColumnAndRow(0, $row);
                            if (!$cell->getFormattedValue()) {
                                continue;
                            }
                            $newModel = new Goods();
                            $cell = $worksheet->getCellByColumnAndRow(0, $row);
                            $newModel->aydi = (string)$cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(1, $row);
                            $newModel->in_the_order = (string)$cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(2, $row);
                            $newModel->generated = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(3, $row);
                            $newModel->name = (string)$cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(4, $row);
                            $newModel->category_id = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(5, $row);
                            $newModel->brand_id = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(6, $row);
                            $newModel->menedjer_id = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(7, $row);
                            $newModel->master_id = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(8, $row);
                            $newModel->fault = (string)$cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(9, $row);
                            $newModel->status_id = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(10, $row);
                            $newModel->komplektaciya = (string)$cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(11, $row);
                            $newModel->buy_price = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(12, $row);
                            $newModel->price_condit = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(13, $row);
                            $newModel->price = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(14, $row);
                            $newModel->paid = $cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(15, $row);
                            $newModel->kommentarii = (string)$cell->getFormattedValue();
                            $cell = $worksheet->getCellByColumnAndRow(16, $row);
                            $newModel->create_at = $cell->getFormattedValue();
                            if (!$newModel->save()) {
                                $error++;
                            }
                            else {
                                $success++;
                            }
                        }
                    }

                    return ['forceReload' => '#crud-datatable-goods', 'title' => "Загружения", 'content' => "Удачно загруженно: {$success} <br/> Ошибка загрузки: {$error}", 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) ];
                    // exit;
                    return ['forceReload' => '#crud-datatable-goods', 'forceClose' => true, ];
                }
                else {
                    return ['forceReload' => '#crud-datatable-goods', 'title' => "Загружения", 'content' => "<span class='text-danger'>Ошибка при загрузке файла</span>", 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-default pull-left', 'data-dismiss' => "modal"]) ];
                }
            }
            else {
                return ['title' => "<span class='text-danger'>Выберите файл</span>", 'size' => 'normal', 'content' => $this->renderAjax('add', ['model' => $model, ]) , 'footer' => Html::button(\Yii::t('app', 'Закрыть') , ['class' => 'btn btn-primary pull-left', 'data-dismiss' => "modal"]) . Html::button(\Yii::t('app', 'Сохранить') , ['class' => 'btn btn-info', 'type' => "submit"]) ];
            }
        }
    }

    /**
     * Temp an existing Nomenclature model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionTemp() {
        $model = new Goods();

        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("creater");
        $objPHPExcel->getProperties()
            ->setLastModifiedBy("Middle field");
        $objPHPExcel->getProperties()
            ->setSubject("Subject");
        $objGet = $objPHPExcel->getActiveSheet();

        $i = 0;
        foreach ($model->attributeLabels() as $attr) {
            $objGet->setCellValueByColumnAndRow($i, 1, $attr);
            $i++;
        }

        $filename = 'temp.xlsx';
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        $objWriter->save('temp.xlsx');

        Yii::$app
            ->response
            ->sendFile('temp.xlsx');
    }

    public function actionUpdateAttr($id, $attr, $value) {
        $model = $this->findModel($id);
        $model->$attr = $value;
        $model->save(false);
    }
    /**
     * Delete an existing Goods model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionDelete($id, $pjax = '#crud-datatable-goods') {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if ($request->isAjax) {
            /*
             *   Process for ajax request
            */
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => $pjax];
        }
        else {
            /*
             *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }

    }

    /**
     * Delete multiple existing Goods model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @return mixed
     */
    public function actionBulkDelete($pjax = '#crud-datatable-goods') {
        $request = Yii::$app->request;
        $pks = explode(',', $request->post('pks')); // Array or selected records primary keys
        foreach ($pks as $pk) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if ($request->isAjax) {
            /*
             *   Process for ajax request
            */
            Yii::$app
                ->response->format = Response::FORMAT_JSON;
            return ['forceClose' => true, 'forceReload' => $pjax];
        }
        else {
            /*
             *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }

    }

    /**
     * Finds the Goods model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @return Goods the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id) {
        if (($model = Goods::findOne($id)) !== null) {
            return $model;
        }
        else {
            throw new NotFoundHttpException('Запрашиваемой страницы не существует.');
        }
    }
}

