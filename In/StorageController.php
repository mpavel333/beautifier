<?php

namespace app\controllers;

use Yii;
use yii\web\UploadedFile;
use PHPExcel;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Style_Fill;
use PHPExcel_RichText;
use app\behaviors\RoleBehavior;
use app\models\Storage;
use app\models\StorageSearch;
use yii\web\Controller;
use app\models\Template;
use app\components\TagsHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use \yii\web\Response;
use yii\helpers\Html;

/**
 * StorageController implements the CRUD actions for Storage model.
 */
class StorageController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'bulk-delete' => ['post'],
                ],
            ],
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'except' => ['application-form'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'role' => [
                'class' => RoleBehavior::class,
                'instanceQuery' => \app\models\Storage::find(),
                'actions' => [
                    'create' => 'storage_create',
                    'update' => 'storage_update',
                    'view' => 'storage_view',
                    'delete' => 'storage_delete',
                    'bulk-delete' => 'storage_delete',
                    'index' => ['storage_view', 'storage_view_all'],
                ],
            ],
        ];
    }
    /**
     * Lists all Storage models.
     * @return mixed
     */
    public function actionIndex()
    {    
        foreach($_SESSION as $key => $value) {
            if(stripos($key, 'form-session')){
                \Yii::$app->session->remove($key);
            }
        }
               
        
        $searchModel = new StorageSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
                return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
    /**
     * Displays a single Storage model.
     * 
     * @return mixed
     */
    public function actionView($id)
    {   
        $request = Yii::$app->request;
        $model = $this->findModel($id);





        if($request->isAjax){
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                    'title'=> \Yii::t('app', '')." #".$id,
                    'content'=>$this->renderAjax('view', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button(\Yii::t('app', 'Отмена'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::a(\Yii::t('app', 'Изменить'),['update', 'id' => $model->id],['class'=>'btn btn-primary','role'=>'modal-remote'])
                ];    
        }else{
            return $this->render('view', [
                'model' => $this->findModel($id),
            ]);
        }
    }




    /**
     * Creates a new Storage model.
     * For ajax request will return json object
     * and for non-ajax request if creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($pjax = '#crud-datatable-storage-pjax', $clouse = false, $atr = null, $value = null) 
    {
        $request = Yii::$app->request;
        $model = new Storage();
        if($request->isGet){
            $model->load(Yii::$app->request->queryParams);
        }


        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($atr != null) {
                $model->$atr = $value;
            }
            if($request->isGet){
 
                return [
                    'title'=>  \Yii::t('app', "Добавить "),
                    'content'=>$this->renderAjax('_form', [
                    'model' => $model,                    ]),
                    'footer'=> Html::button(\Yii::t('app', 'Отмена'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button(\Yii::t('app', 'Создать'),['class'=>'btn btn-primary','type'=>"submit"])
        
                ];         
            }else if($model->load($request->post()) && $model->save()){
                                if ($clouse) {
                    return [
                        'forceReload'=> $pjax,
                        'forceClose' => true,
                    ];
                } else {
                    return [
                        'forceReload'=> $pjax,
                        'title'=>  \Yii::t('app', "Добавить "),
                        'content'=>'<span class="text-success">'.\Yii::t('app', 'Создание  успешно завершено').'</span>',
                        'footer'=> Html::button(\Yii::t('app', 'ОК'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::a(\Yii::t('app', 'Создать еще'),['create'],['class'=>'btn btn-primary','role'=>'modal-remote'])
            
                    ]; 
                }
                
        
            }else{ 
          
                return [
                    'title'=>  \Yii::t('app', "Добавить "),
                    'content'=>$this->renderAjax('_form', [
                        'model' => $model,                    ]),
                    'footer'=> Html::button(\Yii::t('app', 'Отмена'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button(\Yii::t('app', \Yii::t('app', 'Создать')),['class'=>'btn btn-primary','type'=>"submit"])
        
                ];         
            }
        }else{
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                return $this->render('_form', [
                    'model' => $model,                ]);
            }
        }
       
    }

    /**
     * Updates an existing Storage model.
     * For ajax request will return json object
     * and for non-ajax request if update is successful, the browser will be redirected to the 'view' page.
     * 
     * @return mixed
     */
    public function actionUpdate($id, $pjax = '#crud-datatable-storage-pjax')
    {
        $request = Yii::$app->request;
        $model = $this->findModel($id);       
        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            if($request->isGet){
 
                return [
                    'title'=> \Yii::t('app', "Изменить  #").$id,
                    'content'=>$this->renderAjax('_form', [
                        'model' => $model,                    ]),
                    'footer'=> Html::button(\Yii::t('app', 'Отмена'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button(\Yii::t('app', 'Сохранить'),['class'=>'btn btn-primary','type'=>"submit"])
                ];         
            }else if($model->load($request->post()) && $model->save()){

                return [
                    'forceReload'=> $pjax,
                    'forceClose' => true,
                ];
 
                    
            }else{
 
                 return [
                    'title'=> \Yii::t('app', "Изменить  #").$id,
                    'content'=>$this->renderAjax('_form', [
                        'model' => $model,                    ]),
                    'footer'=> Html::button(\Yii::t('app', 'Отмена'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                                Html::button(\Yii::t('app', 'Сохранить'),['class'=>'btn btn-primary','type'=>"submit"])
                ];        
            }
        }else{
            /*
            *   Process for non-ajax request
            */
            if ($model->load($request->post()) && $model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
 
            } else {
                return $this->render('_form', [
                    'model' => $model,                ]);
            }
        }
    }
    
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   

    public function actionAddOld()
    {
        $request=Yii::$app->request;
        $model = new Storage();

        if($request->isAjax){

            Yii::$app->response->format = Response::FORMAT_JSON;
            $model->fileUploading = UploadedFile::getInstance($model, 'fileUploading');
            $error = 0;
            $success = 0;
            if (!empty($model->fileUploading)) {

                try {
                    $filename = 'uploads/'.$model->fileUploading;
                    $model->fileUploading->saveAs($filename);
                    $file = fopen($filename, 'r');
                    if($file) {
                    $Reader = \PHPExcel_IOFactory::createReaderForFile($filename); 
                    $Reader->setReadDataOnly(true); // set this, to not read all excel properties, just data 
                    $objXLS = $Reader->load($filename);
                    foreach ($objXLS->getWorksheetIterator() as $worksheet) {
                        $worksheetTitle     = $worksheet->getTitle();
                        $highestRow         = $worksheet->getHighestRow(); // e.g. 10
                        $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
                        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                        $nrColumns = ord($highestColumn) - 64;
                        for ($row = 2; $row <= $highestRow; ++ $row) {

                            //$cell = $worksheet->getCellByColumnAndRow(0, $row);
                            //if (!$cell->getFormattedValue()) {
                             //   continue;
                            //}
                            $newModel = new Storage();
                                $cell = $worksheet->getCellByColumnAndRow(0, $row);
                            $newModel->type_id  = trim($cell->getFormattedValue());
                                    $relatedModel = \app\models\Manufacturer::find()->where(['name' => $newModel->type_id])->one();
                            if($relatedModel == null){
                                $relatedModel = new \app\models\Manufacturer(['name' => $newModel->type_id]);
                                $relatedModel->save(false);
                            }
                            $newModel->type_id = isset($relatedModel->id) ? $relatedModel->id : null;
                                        $cell = $worksheet->getCellByColumnAndRow(1, $row);
                            $newModel->name  = trim((string)$cell->getFormattedValue());
                                        $cell = $worksheet->getCellByColumnAndRow(2, $row);
                            $newModel->serial  = trim((string)$cell->getFormattedValue());
                                        $cell = $worksheet->getCellByColumnAndRow(3, $row);
                            $newModel->price  = trim($cell->getFormattedValue());
                                        $cell = $worksheet->getCellByColumnAndRow(4, $row);
                            $newModel->cost_price  = trim($cell->getFormattedValue());
                                        $cell = $worksheet->getCellByColumnAndRow(5, $row);
                            $newModel->brand_id  = trim($cell->getFormattedValue());
                                    $relatedModel = \app\models\Brand::find()->where(['name' => $newModel->brand_id])->one();
                            if($relatedModel == null){
                                $relatedModel = new \app\models\Brand(['name' => $newModel->brand_id]);
                                $relatedModel->save(false);
                            }
                            $newModel->brand_id = isset($relatedModel->id) ? $relatedModel->id : null;
                                        $cell = $worksheet->getCellByColumnAndRow(6, $row);
                            $newModel->user_id  = trim($cell->getFormattedValue());
                                    $relatedModel = \app\models\User::find()->where(['name' => $newModel->user_id])->one();
                            if($relatedModel == null){
                                $relatedModel = new \app\models\User(['name' => $newModel->user_id]);
                                $relatedModel->save(false);
                            }
                            $newModel->user_id = isset($relatedModel->id) ? $relatedModel->id : null;
                                        $cell = $worksheet->getCellByColumnAndRow(7, $row);
                            $newModel->create_at  = trim($cell->getFormattedValue());
                                        $cell = $worksheet->getCellByColumnAndRow(8, $row);
                            $newModel->write_off_at  = trim($cell->getFormattedValue());
                                        $cell = $worksheet->getCellByColumnAndRow(9, $row);
                            $newModel->write_off  = trim((string)$cell->getFormattedValue());
                                        if (!$newModel->save()) {
                                $error++;
                            } else {
                                $success++;
                            }
                        }
                    }

                        return [
                            'forceReload'=>'#crud-datatable-storage-pjax',
                            'title'=> "Загружения",
                            'content'=>"Удачно загруженно: {$success} <br/> Ошибка загрузки: {$error}",
                            'footer'=> Html::button(\Yii::t('app', 'Закрыть'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"])
                        ];
                        // exit;
                        return [
                            'forceReload'=>'#crud-datatable-storage-pjax',
                            'forceClose'=>true,
                        ];   
                    } else {
                        return [
                            'forceReload'=>'#crud-datatable-storage-pjax',
                            'title'=> "Загружения",
                            'content'=>"<span class='text-danger'>Ошибка при загрузке файла</span>",
                            'footer'=> Html::button(\Yii::t('app', 'Закрыть'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"])
                        ];
                    }
                } catch (\Exception $e){
                    \Yii::warning($e->getMessage(), "Error while import");
                        return [
                            'forceReload'=>'#crud-datatable-storage-pjax',
                            'title'=> "Загружения",
                            'content'=>"<span class='text-danger'>Ошибка при загрузке файла</span>",
                            'footer'=> Html::button(\Yii::t('app', 'Закрыть'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"])
                        ];
                }

            } else {
                return [
                    'title'=> "<span class='text-danger'>Выберите файл</span>",
                    'size'=>'normal',
                    'content'=>$this->renderAjax('add', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button(\Yii::t('app', 'Закрыть'),['class'=>'btn btn-primary pull-left','data-dismiss'=>"modal"]).
                                Html::button(\Yii::t('app', 'Сохранить'),['class'=>'btn btn-info','type'=>"submit"])
                    ];
            }
        }
    }


    public function actionAdd()
    {
        $request=Yii::$app->request;
        $model = new Storage();

        if($request->isAjax){

            Yii::$app->response->format = Response::FORMAT_JSON;
            $model->fileUploading = UploadedFile::getInstance($model, 'fileUploading');
            $error = 0;
            $success = 0;
            if (!empty($model->fileUploading) || isset($_POST['columns'])) {

                try {

                        if(isset($_POST['columns']) == false){

                            $filename = 'uploads/'.$model->fileUploading;
                            $model->fileUploading->saveAs($filename);

                            return [
                                'forceReload'=>'#crud-datatable-storage-pjax',
                                'title'=> "Загружения",
                                'content'=> $this->renderAjax('@app/views/_excel/settings', [
                                    'excelFile' => $filename,
                                    'class' => Storage::class,
                                ]),
                                'footer'=> Html::button(\Yii::t('app', 'Закрыть'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).Html::button(\Yii::t('app', 'Сохранить'),['class'=>'btn btn-info','type'=>"submit"]),
                            ];

                        } else {
                            $filename = $_POST['excel_file'];
                            $columns = $_POST['columns'];

                            $file = fopen($filename, 'r');
                            if($file) {
                            $Reader = \PHPExcel_IOFactory::createReaderForFile($filename); 
                            $Reader->setReadDataOnly(true); // set this, to not read all excel properties, just data 
                            $objXLS = $Reader->load($filename);
                            foreach ($objXLS->getWorksheetIterator() as $worksheet) {

                                $worksheetTitle     = $worksheet->getTitle();
                                $highestRow         = $worksheet->getHighestRow(); // e.g. 10
                                $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
                                $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                                $nrColumns = ord($highestColumn) - 64;
                                for ($row = 2; $row <= $highestRow; ++ $row) {

                                    $newModel = new Storage();

                                    foreach ($columns as $index => $attribute) {
                                        if($attribute){
                                            $cell = $worksheet->getCellByColumnAndRow($index, $row);
                                            if(Storage::isRelatedAttr($attribute)){
                                                $newModel->$attribute = Storage::getAttributeModelId($attribute, trim($cell->getFormattedValue()));
                                            } else {
                                                $newModel->$attribute = trim($cell->getFormattedValue());
                                            }
                                        }
                                    }

                                    if (!$newModel->save()) {
                                        \Yii::warning($newModel->errors, '$newModel->errors');
                                        $error++;
                                    } else {
                                        $success++;
                                    }
                                }

                                break;
                            }

                                return [
                                    'forceReload'=>'#crud-datatable-storage-pjax',
                                    'title'=> "Загружения",
                                    'content'=>"Удачно загруженно: {$success} <br/> Ошибка загрузки: {$error}",
                                    'footer'=> Html::button(\Yii::t('app', 'Закрыть'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"])
                                ];
                            }
                        }


                        // exit;
                        return [
                            'forceReload'=>'#crud-datatable-storage-pjax',
                            'forceClose'=>true,
                        ];   
                    // } else {
                    //     return [
                    //         'forceReload'=>'#crud-datatable-storage-pjax',
                    //         'title'=> "Загружения",
                    //         'content'=>"<span class='text-danger'>Ошибка при загрузке файла</span>",
                    //         'footer'=> Html::button(\Yii::t('app', 'Закрыть'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"])
                    //     ];
                    // }
                } catch (\Exception $e){
                    \Yii::warning($e->getMessage(), "Error while import");
                        return [
                            'forceReload'=>'#crud-datatable-storage-pjax',
                            'title'=> "Загружения",
                            'content'=>"<span class='text-danger'>Ошибка при загрузке файла</span>",
                            'footer'=> Html::button(\Yii::t('app', 'Закрыть'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"])
                        ];
                }

            } else {
                return [
                    'title'=> "<span class='text-danger'>Выберите файл</span>",
                    'size'=>'normal',
                    'content'=>$this->renderAjax('add', [
                        'model' => $model,
                    ]),
                    'footer'=> Html::button(\Yii::t('app', 'Закрыть'),['class'=>'btn btn-primary pull-left','data-dismiss'=>"modal"]).
                                Html::button(\Yii::t('app', 'Сохранить'),['class'=>'btn btn-info','type'=>"submit"])
                    ];
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
    public function actionTemp()
    {
        $model = new Storage();
		$columns = require('../views/storage/_export_columns.php');


        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("creater");
        $objPHPExcel->getProperties()->setLastModifiedBy("Middle field");
        $objPHPExcel->getProperties()->setSubject("Subject");
        $objGet = $objPHPExcel->getActiveSheet();

        $i = 0;
        foreach ($columns as $column){

            $label = null;

            if(isset($column['visible'])){
                if($column['visible'] == false){
                    continue;
                }
            }

            if(isset($column['label'])){
                $label = $column['label'];
            } elseif(isset($column['attribute'])) {
                $label = $model->getAttributeLabel($column['attribute']);
            }

            $objGet->setCellValueByColumnAndRow($i, 1 , $label);
            $i++;
        }

        $filename = 'temp.xlsx';
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        $objWriter->save('temp.xlsx');

        Yii::$app->response->sendFile('temp.xlsx');
    }
  



    /**
     * Temp an existing Nomenclature model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * 
     * @return mixed
     */
    public function actionExportData()
    {
        $request = \Yii::$app->request;
        \Yii::$app->response->format = Response::FORMAT_JSON;


        if($request->isGet){
            $columns = require('../views/storage/_export_columns.php');


            return [
                'title'=>  \Yii::t('app', "Экспорт"),
                'content'=>$this->renderAjax('export', [
                    'columns' => $columns,
                ]),
                'footer'=> Html::button(\Yii::t('app', 'Отмена'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"]).
                            Html::button(\Yii::t('app', 'Экспорт'),['class'=>'btn btn-primary','type'=>"submit"])
            ]; 
        } elseif($request->isPost){

            $sorting = json_decode($_POST['sorting']);

            $searchModel = new StorageSearch();
            $dataProvider = $searchModel->search(Yii::$app->request->queryParams, true);
            $dataProvider->pagination = false;
            $columnsFormat = require('../views/storage/_export_columns.php');


            $columns = [];

            for($i = 0; $i < count($sorting); $i++){
                $val = $sorting[$i];
                if(isset($columnsFormat[$val])){
                    $columns[] = $columnsFormat[$val];
                }
            }


            $data = [];

            foreach($dataProvider->models as $model2)
            {
                $row = [];

                foreach ($columns as $column){

                    $value = null;

                    if(isset($column['visible'])){
                        if($column['visible'] == false){
                            continue;
                        }
                    }

                    if(isset($column['content'])) {
                        $value = call_user_func($column['content'], $model2);
                    } elseif(isset($column['value'])) {
                        if(is_callable($column['value'])){
                            $value = call_user_func($column['value'], $model2);
                        } else {
                            $value = \yii\helpers\ArrayHelper::getValue($model2, $column['value']);
                        }
                    } else {
                        $attr2 = isset($column['attribute']) ? $column['attribute'] : null;
                        $value = isset($attr2) ? $model2[$attr2] : null;
                    }
                    if ($value != null) {
                        $row[] = $value;
                    } else {
                        if(isset($column['attribute'])){
                            $attribute = $column['attribute'];
                            if($attribute == 'amount'){
                                $row[] = 0;
                            } else {
                                $row[] = null;
                            }
                        } else {
                            $row[] = null;
                        } 
                    }
                }

                $data[] = $row;
            }

            \Yii::warning($data);
            

            $model = new Storage();

            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->getProperties()->setCreator("creater");
            $objPHPExcel->getProperties()->setLastModifiedBy("Middle field");
            $objPHPExcel->getProperties()->setSubject("Subject");
            $objGet = $objPHPExcel->getActiveSheet();

            $i = 0;
            foreach ($columns as $column){

                $label = null;

                if(isset($column['visible'])){
                    if($column['visible'] == false){
                        continue;
                    }
                }

                if(isset($column['label'])){
                    $label = $column['label'];
                } elseif(isset($column['attribute'])) {
                    $label = $model->getAttributeLabel($column['attribute']);
                }

                $objGet->setCellValueByColumnAndRow($i, 1 , $label);
                $i++;
            }

           
            for ($i = 0; $i <= count($data); $i++)
            {
                if(isset($data[$i]) == false){
                    continue;
                }

                $row = $data[$i];
                \Yii::warning($row);

                for ($j = 0; $j <= count($row); $j++)
                {
                    if(isset($row[$j])){
                        $value = $row[$j];
                        // $objGet->setCellValueByColumnAndRow($j, ($i + 1), $value);
                        $objGet->setCellValueByColumnAndRow($j, ($i + 2), strip_tags($value));
                    }
                }
            }

            $filename = 'data.xlsx';
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            $objWriter->save('data.xlsx');

            return [
                'title'=>  \Yii::t('app', "Экспорт"),
                'content'=> '<div class="text-center"><span class="text-success">Файл экспорта успешно сформирован</span></div><a class="btn btn-primary btn-block" href="data.xlsx" download="data.xlsx" style="margin-top: 10px;"><i class="fa fa-download"></i> Скачать</a>',
                'footer'=> Html::button(\Yii::t('app', 'Отмена'),['class'=>'btn btn-default pull-left','data-dismiss'=>"modal"])
            ]; 
        }
    }


    

    public function actionUpdateAttr($id, $attr, $value)
    {
        $model = $this->findModel($id);
        $model->$attr = $value;
        $model->save(false);
    }
    /**
     * Delete an existing Storage model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * 
     * @return mixed
     */
    public function actionDelete($id, $pjax = '#crud-datatable-storage-pjax')
    {
        $request = Yii::$app->request;
        $this->findModel($id)->delete();

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose'=>true,'forceReload'=> $pjax];
        }else{
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }


    }

    public function actionData($attr, $q = null, $id = null)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $out = ['results' => ['id' => '', 'text' => '']];
        if (!is_null($q)) {
            $query = new \yii\db\Query;
            $query->select("id, {$attr} AS text")
                ->from('storage')
                ->where(['like', $attr, $q])
                ->limit(20);
            $command = $query->createCommand();
            $data = $command->queryAll();
            $out['results'] = array_values($data);
        }
        elseif ($id > 0) {
            $out['results'] = ['id' => $id, 'text' => Storage::find($id)->name];
        } else {
            
        }
        return $out;
    }

     /**
     * Delete multiple existing Storage model.
     * For ajax request will return json object
     * and for non-ajax request if deletion is successful, the browser will be redirected to the 'index' page.
     * 
     * @return mixed
     */
    public function actionBulkDelete($pjax = '#crud-datatable-storage-pjax')
    {        
        $request = Yii::$app->request;
        $pks = explode(',', $request->post( 'pks' )); // Array or selected records primary keys
        foreach ( $pks as $pk ) {
            $model = $this->findModel($pk);
            $model->delete();
        }

        if($request->isAjax){
            /*
            *   Process for ajax request
            */
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ['forceClose'=>true,'forceReload'=>$pjax];
        }else{
            /*
            *   Process for non-ajax request
            */
            return $this->redirect(['index']);
        }
       
    }
            

    /**
     * Finds the Storage model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * 
     * @return Storage the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
                if (($model = Storage::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('Запрашиваемой страницы не существует.');
        }
    }


    public function actionExcel1()
    {

            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->getProperties()->setCreator("creater");
            $objPHPExcel->getProperties()->setLastModifiedBy("Middle field");
            $objPHPExcel->getProperties()->setSubject("Subject");

            $objGet = $objPHPExcel->getActiveSheet()->setTitle('Общая информация');

            //ширина колонок
            $objGet->getColumnDimension("A")->setWidth(8.43);
            $objGet->getColumnDimension("B")->setWidth(18.71);
            $objGet->getColumnDimension("C")->setWidth(45);
            $objGet->getColumnDimension("D")->setWidth(63.43);

            //высота ячеек
            
            $objGet->getRowDimension("16")->setRowHeight(60);

            $objGet->getRowDimension("34")->setRowHeight(42);
            $objGet->getRowDimension("35")->setRowHeight(53);
            $objGet->getRowDimension("36")->setRowHeight(41);


            $objGet->getRowDimension("43")->setRowHeight(42);
            $objGet->getRowDimension("44")->setRowHeight(51);

            $rows = [2,4,5,6,7,8,9,10,11,12,13,14,17,19,21,22,
                     23,24,25,26,27,28,29,30,31,32,33,
                     37,38,39,40,41,42,46];

            foreach($rows as $key=>$value){
                $objGet->getRowDimension($value)->setRowHeight(30);
            }


            //стили
            $style1 = array(
                'font' => array(
                    //'name' => 'Calibri',
                    'size' => 16,  
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                )
            );

            $style2 = array(
                //'font' => array(
                //    'name' => 'Calibri',
                //    'size' => 11,  
                //),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                    'wrap'=> true
                )
            );

            $style3 = array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => 'E2EFDA')
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                ),
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => 'DDDDDD')
                    )
                )
            );

            $style4 = array(
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                    'wrap'=> true
                )
            );

            $style5 = array(
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                    'wrap'=> true,
                    'rotation' => 90
                )
            );

            $style6 = array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => 'D6DCE4')
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                ),
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => 'DDDDDD')
                    )
                )
            );


            /******************************/
                      
            $objGet->mergeCells("B2:D2");
            $objGet->setCellValue("B2", "Общая информация о заявке");
            $objGet->getStyle("B2")->applyFromArray($style1); 

            $objGet->mergeCells("B3:D3");
            
            $objGet->mergeCells("B4:C4");
            $objGet->setCellValue("B4", "Категория заявки");
            $objGet->getStyle("B4")->applyFromArray($style2);

            $objGet->mergeCells("B5:C5");
            $objGet->setCellValue("B5", "Подкатегория");
            $objGet->getStyle("B5")->applyFromArray($style2);

            $objGet->mergeCells("B6:C6");
            $objGet->setCellValue("B6", "Проект реализуется в зоне исторической среды");
            $objGet->getStyle("B6")->applyFromArray($style2);

            $objGet->mergeCells("B7:C7");
            $objGet->setCellValue("B7", "Название региона");
            $objGet->getStyle("B7")->applyFromArray($style2);

            //
            $objGet->getStyle("D4")->applyFromArray($style3);
            $objGet->getStyle("D5")->applyFromArray($style3);
            $objGet->getStyle("D6")->applyFromArray($style3);
            $objGet->getStyle("D7")->applyFromArray($style3);
            //

            $objGet->mergeCells("B8:D8");

            $objGet->mergeCells("B9:B14");
            $objGet->setCellValue("B9", "Название города / поселения");  
            $objGet->getStyle("B9")->applyFromArray($style4);

            $objGet->setCellValue("C9", "Муниципальный район");
            $objGet->getStyle("C9")->applyFromArray($style2);

            $objGet->setCellValue("C10", "Муниципальное образование");
            $objGet->getStyle("C10")->applyFromArray($style2);

            $objGet->setCellValue("C11", "Тип муниципального образования");
            $objGet->getStyle("C11")->applyFromArray($style2);

            $objGet->setCellValue("C12", "Населённый пункт");
            $objGet->getStyle("C12")->applyFromArray($style2);

            $objGet->setCellValue("C13", "ОКТМО");
            $objGet->getStyle("C13")->applyFromArray($style2);

            $objGet->setCellValue("C14", "Численность населения населенного пункта");
            $objGet->getStyle("C14")->applyFromArray($style2);

            //
            $objGet->getStyle("D9")->applyFromArray($style3);
            $objGet->getStyle("D10")->applyFromArray($style3);
            $objGet->getStyle("D11")->applyFromArray($style3);
            $objGet->getStyle("D12")->applyFromArray($style3);
            $objGet->getStyle("D13")->applyFromArray($style3);
            $objGet->getStyle("D14")->applyFromArray($style3);
            //

            
            $objGet->mergeCells("B15:D15");

            $objGet->mergeCells("B16:C16");
            $objGet->setCellValue("B16", "Наименование проекта создания комфортной городской среды");
            $objGet->getStyle("B16")->applyFromArray($style2);

            $objGet->mergeCells("B17:C17");
            $objGet->setCellValue("B17", "Тип благоустраиваемой территории");
            $objGet->getStyle("B17")->applyFromArray($style2);

            //
            $objGet->getStyle("D16")->applyFromArray($style3);
            $objGet->getStyle("D17")->applyFromArray($style3);
            //

            $objGet->mergeCells("B18:D18");

            $objGet->mergeCells("B19:D19");
            $objGet->setCellValue("B19", "5.9 Основные показатели проекта");
            $objGet->getStyle("B19")->applyFromArray($style1); 


            $objGet->mergeCells("B20:D20");


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Источники финансирования реализации проекта, ');
            $objBold = $objRichText->createTextRun('тыс.руб.');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B21:B32");
            $objGet->setCellValue("B21", $objRichText); 
            $objGet->getStyle("B21")->applyFromArray($style5); 
            

            $objGet->mergeCells("C21:D21");
            $objGet->setCellValue("C21", "2025 год");   
            $objGet->getStyle("C21")->applyFromArray($style6);
            
            $objGet->setCellValue("C22", "Средства государственной субсидии из федерального бюджета"); 
            $objGet->getStyle("C22")->applyFromArray($style2);

            $objGet->setCellValue("C23", "Региональный бюджет"); 
            $objGet->getStyle("C23")->applyFromArray($style2);

            $objGet->setCellValue("C24", "Муниципальный бюджет"); 
            $objGet->getStyle("C24")->applyFromArray($style2);

            $objGet->setCellValue("C25", "Внебюджетные источники"); 
            $objGet->getStyle("C25")->applyFromArray($style2);

            $objGet->setCellValue("C26", "Итого в 2024 году");      
            $objGet->getStyle("C26")->applyFromArray($style2);

            $objGet->mergeCells("C27:D27");
            $objGet->setCellValue("C27", "2026 год"); 
            $objGet->getStyle("C27")->applyFromArray($style6);

            $objGet->setCellValue("C28", "Региональный бюджет"); 
            $objGet->getStyle("C28")->applyFromArray($style2);

            $objGet->setCellValue("C29", "Муниципальный бюджет"); 
            $objGet->getStyle("C29")->applyFromArray($style2);

            $objGet->setCellValue("C30", "Внебюджетные источники"); 
            $objGet->getStyle("C30")->applyFromArray($style2);

            $objGet->setCellValue("C31", "Итого в 2025 году"); 
            $objGet->getStyle("C31")->applyFromArray($style2);

            $objGet->setCellValue("C32", "Итого расходов на реализацию проекта"); 
            $objGet->getStyle("C32")->applyFromArray($style2);

            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Площадь территорий, благоустраиваемых в рамках проекта, ');
            $objBold = $objRichText->createTextRun('кв.м');
            $objBold->getFont()->setBold(true);            

            $objGet->mergeCells("B33:C33");
            $objGet->setCellValue("B33", $objRichText); 
            $objGet->getStyle("B33")->applyFromArray($style2);

            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Эксплуатационные расходы (в год) на содержание благоустроенных территорий, ');
            $objBold = $objRichText->createTextRun('тыс.руб.');
            $objBold->getFont()->setBold(true); 

            $objGet->mergeCells("B34:C34");
            $objGet->setCellValue("B34", $objRichText); 
            $objGet->getStyle("B34")->applyFromArray($style2);


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Доходы регионального и/или муниципального бюджета (в год) от эксплуатации территории, на которой будет реализовываться проект, ');
            $objBold = $objRichText->createTextRun('тыс.руб.');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B35:C35");
            $objGet->setCellValue("B35", $objRichText); 
            $objGet->getStyle("B35")->applyFromArray($style2);


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Рост посещаемости общественной территории, на которой будет реализовываться проект (пешеходный трафик), ');
            $objBold = $objRichText->createTextRun('чел. в год');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B36:C36");
            $objGet->setCellValue("B36", $objRichText); 
            $objGet->getStyle("B36")->applyFromArray($style2);


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Количество созданных рабочих мест, ');
            $objBold = $objRichText->createTextRun('чел.');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B37:B42");
            $objGet->setCellValue("B37", $objRichText); 
            $objGet->getStyle("B37")->applyFromArray($style5);
            
            

            $objGet->setCellValue("C37", "В социальной сфере");
            $objGet->getStyle("C37")->applyFromArray($style2);

            $objGet->setCellValue("C38", "В торговле и услугах"); 
            $objGet->getStyle("C38")->applyFromArray($style2);

            $objGet->setCellValue("C39", "В производстве"); 
            $objGet->getStyle("C39")->applyFromArray($style2);

            $objGet->setCellValue("C40", "В лесном хозяйстве"); 
            $objGet->getStyle("C40")->applyFromArray($style2);

            $objGet->setCellValue("C41", "Иное");
            $objGet->getStyle("C41")->applyFromArray($style2);

            $objGet->setCellValue("C42", "Итого созданных рабочих мест");
            $objGet->getStyle("C42")->applyFromArray($style2);

            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Количество жителей населенного пункта, вовлеченных в решение вопросов, связанных с разработкой проекта (всего), ');
            $objBold = $objRichText->createTextRun('чел.');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B43:C43");
            $objGet->setCellValue("B43", $objRichText); 
            $objGet->getStyle("B43")->applyFromArray($style2);


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('из них: количество граждан в возрасте 14 лет и старше, принявших участие в решении вопросов, связанных с разработкой проекта, ');
            $objBold = $objRichText->createTextRun('чел.');
            $objBold->getFont()->setBold(true);
           
            $objGet->mergeCells("B44:C44");
            $objGet->setCellValue("B44", $objRichText); 
            $objGet->getStyle("B44")->applyFromArray($style2);

            $objGet->mergeCells("B45:D45");

            $objGet->mergeCells("B46:C46");
            $objGet->setCellValue("B46", "Координаты территории, на которой будет реализовываться проект"); 
            $objGet->getStyle("B46")->applyFromArray($style2);

            //
            $objGet->getStyle("D22")->applyFromArray($style3);
            $objGet->getStyle("D23")->applyFromArray($style3);
            $objGet->getStyle("D24")->applyFromArray($style3);
            $objGet->getStyle("D25")->applyFromArray($style3);
            $objGet->getStyle("D26")->applyFromArray($style3);

            $objGet->getStyle("D28")->applyFromArray($style3);
            $objGet->getStyle("D29")->applyFromArray($style3);
            $objGet->getStyle("D30")->applyFromArray($style3);
            $objGet->getStyle("D31")->applyFromArray($style3);
            $objGet->getStyle("D32")->applyFromArray($style3);
            $objGet->getStyle("D33")->applyFromArray($style3);
            $objGet->getStyle("D34")->applyFromArray($style3);
            $objGet->getStyle("D35")->applyFromArray($style3);
            $objGet->getStyle("D36")->applyFromArray($style3);
            $objGet->getStyle("D37")->applyFromArray($style3);
            $objGet->getStyle("D38")->applyFromArray($style3);
            $objGet->getStyle("D39")->applyFromArray($style3);
            $objGet->getStyle("D40")->applyFromArray($style3);
            $objGet->getStyle("D41")->applyFromArray($style3);
            $objGet->getStyle("D42")->applyFromArray($style3);
            $objGet->getStyle("D43")->applyFromArray($style3);
            $objGet->getStyle("D44")->applyFromArray($style3);
            $objGet->getStyle("D46")->applyFromArray($style3);
            //


            // Лист 2

            $objPHPExcel->createSheet(2);
            $objPHPExcel->setActiveSheetIndex(1);
            $objGet2 = $objPHPExcel->getActiveSheet()->setTitle('Для соглашения');

            //ширина колонок
            $objGet2->getColumnDimension("A")->setWidth(8.43);
            $objGet2->getColumnDimension("B")->setWidth(4.68);
            $objGet2->getColumnDimension("C")->setWidth(44);
            $objGet2->getColumnDimension("D")->setWidth(3);
            $objGet2->getColumnDimension("E")->setWidth(6.29);
            $objGet2->getColumnDimension("F")->setWidth(35);
            $objGet2->getColumnDimension("G")->setWidth(20);
            $objGet2->getColumnDimension("H")->setWidth(20);
            $objGet2->getColumnDimension("I")->setWidth(20);
            $objGet2->getColumnDimension("J")->setWidth(20);
            $objGet2->getColumnDimension("K")->setWidth(8.43);
            $objGet2->getColumnDimension("L")->setWidth(8.43);
            $objGet2->getColumnDimension("M")->setWidth(8.43);
            $objGet2->getColumnDimension("N")->setWidth(8.43);
            $objGet2->getColumnDimension("O")->setWidth(8.43);


            //высота ячеек
            
            $objGet2->getRowDimension("3")->setRowHeight(60);

            //стили

            $style7 = array(
                'font' => array(
                    'bold' => true,
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                    'wrap'=> true,
                ),

            );


            //

            $objGet2->mergeCells("B2:B3");
            $objGet2->setCellValue("B2", '№'); 
            $objGet2->getStyle("B2")->applyFromArray($style7);

            $objGet2->mergeCells("C2:C3");
            $objGet2->setCellValue("C2", 'Наименование функциональной зоны'); 
            $objGet2->getStyle("C2")->applyFromArray($style7);

            $objGet2->mergeCells("E2:E3");
            $objGet2->setCellValue("E2", '№'); 
            $objGet2->getStyle("E2")->applyFromArray($style7);

            $objGet2->mergeCells("F2:F3");
            $objGet2->setCellValue("F2", 'Наименование мероприятия'); 
            $objGet2->getStyle("F2")->applyFromArray($style7);

            $objGet2->mergeCells("G2:J2");
            $objGet2->setCellValue("G2", 'Источники финансирования'); 
            $objGet2->getStyle("G2")->applyFromArray($style7);

            $objGet2->setCellValue("G3", 'Средства господдержки из федерального бюджета'); 
            $objGet2->getStyle("G3")->applyFromArray($style7);
            
            $objGet2->setCellValue("H3", 'Региональный бюджет'); 
            $objGet2->getStyle("H3")->applyFromArray($style7);

            $objGet2->setCellValue("I3", 'Муниципальный бюджет'); 
            $objGet2->getStyle("I3")->applyFromArray($style7);

            $objGet2->setCellValue("I3", 'Муниципальный бюджет'); 
            $objGet2->getStyle("I3")->applyFromArray($style7);

            $objGet2->setCellValue("J3", 'Внебюджетные источники'); 
            $objGet2->getStyle("J3")->applyFromArray($style7);

            

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            $objWriter->save('excel1.xlsx');


    }

    public function actionExcel2()
    {

            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->getProperties()->setCreator("creater");
            $objPHPExcel->getProperties()->setLastModifiedBy("Middle field");
            $objPHPExcel->getProperties()->setSubject("Subject");

            $objGet = $objPHPExcel->getActiveSheet()->setTitle('Общая информация');

            //ширина колонок
            $objGet->getColumnDimension("A")->setWidth(8.43);
            $objGet->getColumnDimension("B")->setWidth(18.71);
            $objGet->getColumnDimension("C")->setWidth(45);
            $objGet->getColumnDimension("D")->setWidth(63.43);

            //высота ячеек
            
            $objGet->getRowDimension("16")->setRowHeight(60);

            $objGet->getRowDimension("34")->setRowHeight(42);
            $objGet->getRowDimension("35")->setRowHeight(53);
            $objGet->getRowDimension("36")->setRowHeight(41);


            $objGet->getRowDimension("43")->setRowHeight(42);
            $objGet->getRowDimension("44")->setRowHeight(51);

            $rows = [2,4,5,6,7,8,9,10,11,12,13,14,17,19,21,22,
                     23,24,25,26,27,28,29,30,31,32,33,
                     37,38,39,40,41,42,46,48];

            foreach($rows as $key=>$value){
                $objGet->getRowDimension($value)->setRowHeight(30);
            }


            //стили
            $style1 = array(
                'font' => array(
                    //'name' => 'Calibri',
                    'size' => 16,  
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                )
            );

            $style2 = array(
                //'font' => array(
                //    'name' => 'Calibri',
                //    'size' => 11,  
                //),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                    'wrap'=> true
                )
            );

            $style3 = array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => 'E2EFDA')
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                ),
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => 'DDDDDD')
                    )
                )
            );

            $style4 = array(
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                    'wrap'=> true
                )
            );

            $style5 = array(
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                    'wrap'=> true,
                    'rotation' => 90
                )
            );

            $style6 = array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'color' => array('rgb' => 'D6DCE4')
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                ),
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN,
                        'color' => array('rgb' => 'DDDDDD')
                    )
                )
            );


            /******************************/
                      
            $objGet->mergeCells("B2:D2");
            $objGet->setCellValue("B2", "Общая информация о заявке");
            $objGet->getStyle("B2")->applyFromArray($style1); 

            $objGet->mergeCells("B3:D3");
            
            $objGet->mergeCells("B4:C4");
            $objGet->setCellValue("B4", "Категория заявки");
            $objGet->getStyle("B4")->applyFromArray($style2);

            $objGet->mergeCells("B5:C5");
            $objGet->setCellValue("B5", "Подкатегория");
            $objGet->getStyle("B5")->applyFromArray($style2);

            $objGet->mergeCells("B6:C6");
            $objGet->setCellValue("B6", "Сумма гранта");
            $objGet->getStyle("B6")->applyFromArray($style2);

            $objGet->mergeCells("B7:C7");
            $objGet->setCellValue("B7", "Название региона");
            $objGet->getStyle("B7")->applyFromArray($style2);

            //
            $objGet->getStyle("D4")->applyFromArray($style3);
            $objGet->getStyle("D5")->applyFromArray($style3);
            $objGet->getStyle("D6")->applyFromArray($style3);
            $objGet->getStyle("D7")->applyFromArray($style3);
            //

            $objGet->mergeCells("B8:D8");

            $objGet->mergeCells("B9:B14");
            $objGet->setCellValue("B9", "Название города / поселения");  
            $objGet->getStyle("B9")->applyFromArray($style4);

            $objGet->setCellValue("C9", "Муниципальный район");
            $objGet->getStyle("C9")->applyFromArray($style2);

            $objGet->setCellValue("C10", "Муниципальное образование");
            $objGet->getStyle("C10")->applyFromArray($style2);

            $objGet->setCellValue("C11", "Тип муниципального образования");
            $objGet->getStyle("C11")->applyFromArray($style2);

            $objGet->setCellValue("C12", "Населённый пункт");
            $objGet->getStyle("C12")->applyFromArray($style2);

            $objGet->setCellValue("C13", "ОКТМО");
            $objGet->getStyle("C13")->applyFromArray($style2);

            $objGet->setCellValue("C14", "Численность населения населенного пункта");
            $objGet->getStyle("C14")->applyFromArray($style2);

            //
            $objGet->getStyle("D9")->applyFromArray($style3);
            $objGet->getStyle("D10")->applyFromArray($style3);
            $objGet->getStyle("D11")->applyFromArray($style3);
            $objGet->getStyle("D12")->applyFromArray($style3);
            $objGet->getStyle("D13")->applyFromArray($style3);
            $objGet->getStyle("D14")->applyFromArray($style3);
            //

            
            $objGet->mergeCells("B15:D15");

            $objGet->mergeCells("B16:C16");
            $objGet->setCellValue("B16", "Наименование проекта создания комфортной городской среды");
            $objGet->getStyle("B16")->applyFromArray($style2);

            $objGet->mergeCells("B17:C17");
            $objGet->setCellValue("B17", "Тип благоустраиваемой территории");
            $objGet->getStyle("B17")->applyFromArray($style2);

            //
            $objGet->getStyle("D16")->applyFromArray($style3);
            $objGet->getStyle("D17")->applyFromArray($style3);
            //

            $objGet->mergeCells("B18:D18");

            $objGet->mergeCells("B19:D19");
            $objGet->setCellValue("B19", "5.9 Основные показатели проекта");
            $objGet->getStyle("B19")->applyFromArray($style1); 


            $objGet->mergeCells("B20:D20");


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Источники финансирования реализации проекта, ');
            $objBold = $objRichText->createTextRun('тыс.руб.');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B21:B32");
            $objGet->setCellValue("B21", $objRichText); 
            $objGet->getStyle("B21")->applyFromArray($style5); 
            

            $objGet->mergeCells("C21:D21");
            $objGet->setCellValue("C21", "2025 год");   
            $objGet->getStyle("C21")->applyFromArray($style6);
            
            $objGet->setCellValue("C22", "Средства государственной субсидии из федерального бюджета"); 
            $objGet->getStyle("C22")->applyFromArray($style2);

            $objGet->setCellValue("C23", "Региональный бюджет"); 
            $objGet->getStyle("C23")->applyFromArray($style2);

            $objGet->setCellValue("C24", "Муниципальный бюджет"); 
            $objGet->getStyle("C24")->applyFromArray($style2);

            $objGet->setCellValue("C25", "Внебюджетные источники"); 
            $objGet->getStyle("C25")->applyFromArray($style2);

            $objGet->setCellValue("C26", "Итого в 2025 году");      
            $objGet->getStyle("C26")->applyFromArray($style2);

            $objGet->mergeCells("C27:D27");
            $objGet->setCellValue("C27", "2026 год"); 
            $objGet->getStyle("C27")->applyFromArray($style6);

            $objGet->setCellValue("C28", "Региональный бюджет"); 
            $objGet->getStyle("C28")->applyFromArray($style2);

            $objGet->setCellValue("C29", "Муниципальный бюджет"); 
            $objGet->getStyle("C29")->applyFromArray($style2);

            $objGet->setCellValue("C30", "Внебюджетные источники"); 
            $objGet->getStyle("C30")->applyFromArray($style2);

            $objGet->setCellValue("C31", "Итого в 2026 году"); 
            $objGet->getStyle("C31")->applyFromArray($style2);

            $objGet->setCellValue("C32", "Итого расходов на реализацию проекта"); 
            $objGet->getStyle("C32")->applyFromArray($style2);

            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Площадь территорий, благоустраиваемых в рамках проекта, ');
            $objBold = $objRichText->createTextRun('кв.м');
            $objBold->getFont()->setBold(true);            

            $objGet->mergeCells("B33:C33");
            $objGet->setCellValue("B33", $objRichText); 
            $objGet->getStyle("B33")->applyFromArray($style2);

            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Эксплуатационные расходы (в год) на содержание благоустроенных территорий, ');
            $objBold = $objRichText->createTextRun('тыс.руб.');
            $objBold->getFont()->setBold(true); 

            $objGet->mergeCells("B34:C34");
            $objGet->setCellValue("B34", $objRichText); 
            $objGet->getStyle("B34")->applyFromArray($style2);


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Доходы регионального и/или муниципального бюджета (в год) от эксплуатации территории, на которой будет реализовываться проект, ');
            $objBold = $objRichText->createTextRun('тыс.руб.');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B35:C35");
            $objGet->setCellValue("B35", $objRichText); 
            $objGet->getStyle("B35")->applyFromArray($style2);


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Рост посещаемости общественной территории, на которой будет реализовываться проект (пешеходный трафик), ');
            $objBold = $objRichText->createTextRun('чел. в год');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B36:C36");
            $objGet->setCellValue("B36", $objRichText); 
            $objGet->getStyle("B36")->applyFromArray($style2);


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Количество созданных рабочих мест, ');
            $objBold = $objRichText->createTextRun('чел.');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B37:B42");
            $objGet->setCellValue("B37", $objRichText); 
            $objGet->getStyle("B37")->applyFromArray($style5);
            
            

            $objGet->setCellValue("C37", "В социальной сфере");
            $objGet->getStyle("C37")->applyFromArray($style2);

            $objGet->setCellValue("C38", "В торговле и услугах"); 
            $objGet->getStyle("C38")->applyFromArray($style2);

            $objGet->setCellValue("C39", "В производстве"); 
            $objGet->getStyle("C39")->applyFromArray($style2);

            $objGet->setCellValue("C40", "В лесном хозяйстве"); 
            $objGet->getStyle("C40")->applyFromArray($style2);

            $objGet->setCellValue("C41", "Иное");
            $objGet->getStyle("C41")->applyFromArray($style2);

            $objGet->setCellValue("C42", "Итого созданных рабочих мест");
            $objGet->getStyle("C42")->applyFromArray($style2);

            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('Количество жителей населенного пункта, вовлеченных в решение вопросов, связанных с разработкой проекта (всего), ');
            $objBold = $objRichText->createTextRun('чел.');
            $objBold->getFont()->setBold(true);

            $objGet->mergeCells("B43:C43");
            $objGet->setCellValue("B43", $objRichText); 
            $objGet->getStyle("B43")->applyFromArray($style2);


            $objRichText = new PHPExcel_RichText();
            $objRichText->createText('из них: количество граждан в возрасте 14 лет и старше, принявших участие в решении вопросов, связанных с разработкой проекта, ');
            $objBold = $objRichText->createTextRun('чел.');
            $objBold->getFont()->setBold(true);
           
            $objGet->mergeCells("B44:C44");
            $objGet->setCellValue("B44", $objRichText); 
            $objGet->getStyle("B44")->applyFromArray($style2);

            $objGet->mergeCells("B45:D45");

            $objGet->mergeCells("B46:D46");
            $objGet->setCellValue("B46", "5.11 Координаты территории");
            $objGet->getStyle("B46")->applyFromArray($style1); 

            $objGet->mergeCells("B47:D47");

            $objGet->mergeCells("B48:C48");
            $objGet->setCellValue("B48", "Координаты территории, на которой будет реализовываться проект"); 
            $objGet->getStyle("B48")->applyFromArray($style2);

            //
            $objGet->getStyle("D22")->applyFromArray($style3);
            $objGet->getStyle("D23")->applyFromArray($style3);
            $objGet->getStyle("D24")->applyFromArray($style3);
            $objGet->getStyle("D25")->applyFromArray($style3);
            $objGet->getStyle("D26")->applyFromArray($style3);

            $objGet->getStyle("D28")->applyFromArray($style3);
            $objGet->getStyle("D29")->applyFromArray($style3);
            $objGet->getStyle("D30")->applyFromArray($style3);
            $objGet->getStyle("D31")->applyFromArray($style3);
            $objGet->getStyle("D32")->applyFromArray($style3);
            $objGet->getStyle("D33")->applyFromArray($style3);
            $objGet->getStyle("D34")->applyFromArray($style3);
            $objGet->getStyle("D35")->applyFromArray($style3);
            $objGet->getStyle("D36")->applyFromArray($style3);
            $objGet->getStyle("D37")->applyFromArray($style3);
            $objGet->getStyle("D38")->applyFromArray($style3);
            $objGet->getStyle("D39")->applyFromArray($style3);
            $objGet->getStyle("D40")->applyFromArray($style3);
            $objGet->getStyle("D41")->applyFromArray($style3);
            $objGet->getStyle("D42")->applyFromArray($style3);
            $objGet->getStyle("D43")->applyFromArray($style3);
            $objGet->getStyle("D44")->applyFromArray($style3);
            $objGet->getStyle("D46")->applyFromArray($style3);
            $objGet->getStyle("D48")->applyFromArray($style3);
            //


            // Лист 2

            $objPHPExcel->createSheet(2);
            $objPHPExcel->setActiveSheetIndex(1);
            $objGet2 = $objPHPExcel->getActiveSheet()->setTitle('Для соглашения');

            //ширина колонок
            $objGet2->getColumnDimension("A")->setWidth(8.43);
            $objGet2->getColumnDimension("B")->setWidth(4.68);
            $objGet2->getColumnDimension("C")->setWidth(44);
            $objGet2->getColumnDimension("D")->setWidth(3);
            $objGet2->getColumnDimension("E")->setWidth(6.29);
            $objGet2->getColumnDimension("F")->setWidth(35);
            $objGet2->getColumnDimension("G")->setWidth(20);
            $objGet2->getColumnDimension("H")->setWidth(20);
            $objGet2->getColumnDimension("I")->setWidth(20);
            $objGet2->getColumnDimension("J")->setWidth(20);
            $objGet2->getColumnDimension("K")->setWidth(8.43);
            $objGet2->getColumnDimension("L")->setWidth(8.43);
            $objGet2->getColumnDimension("M")->setWidth(8.43);
            $objGet2->getColumnDimension("N")->setWidth(8.43);
            $objGet2->getColumnDimension("O")->setWidth(8.43);


            //высота ячеек
            
            $objGet2->getRowDimension("3")->setRowHeight(60);

            //стили

            $style7 = array(
                'font' => array(
                    'bold' => true,
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'indent' => 2,
                    'wrap'=> true,
                ),

            );


            //

            $objGet2->mergeCells("B2:B3");
            $objGet2->setCellValue("B2", '№'); 
            $objGet2->getStyle("B2")->applyFromArray($style7);

            $objGet2->mergeCells("C2:C3");
            $objGet2->setCellValue("C2", 'Наименование функциональной зоны'); 
            $objGet2->getStyle("C2")->applyFromArray($style7);

            $objGet2->mergeCells("E2:E3");
            $objGet2->setCellValue("E2", '№'); 
            $objGet2->getStyle("E2")->applyFromArray($style7);

            $objGet2->mergeCells("F2:F3");
            $objGet2->setCellValue("F2", 'Наименование мероприятия'); 
            $objGet2->getStyle("F2")->applyFromArray($style7);

            $objGet2->mergeCells("G2:J2");
            $objGet2->setCellValue("G2", 'Источники финансирования'); 
            $objGet2->getStyle("G2")->applyFromArray($style7);

            $objGet2->setCellValue("G3", 'Средства господдержки из федерального бюджета'); 
            $objGet2->getStyle("G3")->applyFromArray($style7);
            
            $objGet2->setCellValue("H3", 'Региональный бюджет'); 
            $objGet2->getStyle("H3")->applyFromArray($style7);

            $objGet2->setCellValue("I3", 'Муниципальный бюджет'); 
            $objGet2->getStyle("I3")->applyFromArray($style7);

            $objGet2->setCellValue("I3", 'Муниципальный бюджет'); 
            $objGet2->getStyle("I3")->applyFromArray($style7);

            $objGet2->setCellValue("J3", 'Внебюджетные источники'); 
            $objGet2->getStyle("J3")->applyFromArray($style7);

            

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

            $objWriter->save('excel2.xlsx');


    }




}
