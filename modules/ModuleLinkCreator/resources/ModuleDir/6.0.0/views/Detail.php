<?php
class __ModuleName___Detail_View extends Vtiger_Detail_View
{
    public function checkPermission(Vtiger_Request $request) {
        require_once "modules/__ModuleName__/helpers/Util.php";
        $helpersUtil=new CustomModule_Util_Helper();
        if($helpersUtil->checkPermis()){
            return parent::checkPermission($request);
        }
    }

    function __construct() {
        parent::__construct();
        $this->exposeMethod('showRelatedRecords');
    }
    /**
     * Function to get activities
     * @param Vtiger_Request $request
     * @return <List of activity models>
     */
    public function getActivities(Vtiger_Request $request) {
        $moduleName = 'Calendar';
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if($currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            $moduleName = $request->getModule();
            $recordId = $request->get('record');

            $pageNumber = $request->get('page');
            if(empty ($pageNumber)) {
                $pageNumber = 1;
            }
            $pagingModel = new Vtiger_Paging_Model();
            $pagingModel->set('page', $pageNumber);
            $pagingModel->set('limit', 10);

            if(!$this->record) {
                $this->record = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
            }
            $recordModel = $this->record->getRecord();
            $moduleModel = $recordModel->getModule();

            $relatedActivities = $moduleModel->getCalendarActivities('', $pagingModel, 'all', $recordId);

            $viewer = $this->getViewer($request);
            $viewer->assign('RECORD', $recordModel);
            $viewer->assign('MODULE_NAME', $moduleName);
            $viewer->assign('PAGING_MODEL', $pagingModel);
            $viewer->assign('PAGE_NUMBER', $pageNumber);
            $viewer->assign('ACTIVITIES', $relatedActivities);

            return $viewer->view('RelatedActivities.tpl', $moduleName, true);
        }
    }

    /**
     * Function sends all the comments for a parent(Accounts, Contacts etc)
     * @param Vtiger_Request $request
     * @return <type>
     */
    function showAllComments(Vtiger_Request $request) {
        $parentRecordId = $request->get('record');
        $commentRecordId = $request->get('commentid');
        $moduleName = $request->getModule();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');

        $parentCommentModels = ModComments_Record_Model::getAllParentComments($parentRecordId,$moduleName);

        if(!empty($commentRecordId)) {
            $currentCommentModel = ModComments_Record_Model::getInstanceById($commentRecordId);
        }

        $viewer = $this->getViewer($request);
        $recordModel = Vtiger_Record_Model::getInstanceById($parentRecordId);
        $link = $recordModel->getDetailViewUrl();

        $viewer->assign('LINKDETAILVIEW', $link);
        $viewer->assign('CURRENTUSER', $currentUserModel);
        $viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
        $viewer->assign('PARENT_COMMENTS', $parentCommentModels);
        $viewer->assign('CURRENT_COMMENT', $currentCommentModel);

        return $viewer->view('ShowAllComments.tpl', $moduleName, 'true');
    }

    function showChildComments(Vtiger_Request $request) {
        $parentCommentId = $request->get('commentid');
        $parentCommentModel = ModComments_Record_Model::getInstanceById($parentCommentId);
        $childComments = $parentCommentModel->getChildComments();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');

        $viewer = $this->getViewer($request);
        $recordModel = Vtiger_Record_Model::getInstanceById($request->get('record'));
        $link = $recordModel->getDetailViewUrl();

        $viewer->assign('LINKDETAILVIEW', $link);
        $viewer->assign('PARENT_COMMENTS', $childComments);
        $viewer->assign('CURRENTUSER', $currentUserModel);
        $viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
        $moduleName = $request->getModule();

        return $viewer->view('CommentsList.tpl', $moduleName, 'true');
    }
}