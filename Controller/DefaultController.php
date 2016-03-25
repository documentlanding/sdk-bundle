<?php

namespace DocumentLanding\SdkBundle\Controller;

use DocumentLanding\SdkBundle\DocumentLandingSdkBundleEvents;
use DocumentLanding\SdkBundle\Events\NewLeadEvent;
use DocumentLanding\SdkBundle\Events\UpdatedLeadEvent;
use DocumentLanding\SdkBundle\Events\ApiRequestEvent;
use DocumentLanding\SdkBundle\Events\RefreshTokenEvent;
use DocumentLanding\SdkBundle\Events\LoadLeadEvent;
use DocumentLanding\SdkBundle\Events\PreUpdateSchemaEvent;
use DocumentLanding\SdkBundle\Events\PostUpdateSchemaEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;


class DefaultController extends Controller
{

    protected $entityMetadata;
    protected $remoteTest = false;

    /**
     * @Route("/config/success", name="config_success")
     */
    public function configSuccessAction(Request $request)
    {
        return $this->render('SdkBundle:Setup:confirmed.html.twig');
    }

    /**
     * @Route("/oauth/token", name="oauth_token")
     * @Method({"POST"})
     */
    public function oauthTokenAction(Request $request)
    {
	    $statusCode = 200;
	    $responseData = array();
        $data = array();
        $content = $request->getContent();

        if (!empty($content)) {
            $data = json_decode($content, true);
            if (!isset($data['refresh_token'])) {
	            $statusCode = 422;
            }
            else {
                if ($config['access_token']) {
	                return $config['access_token'];
                }
                else {
	                $event = new RefreshTokenEvent($request);
                    $dispatcher = $this->container->get('event_dispatcher');
                    $dispatcher->dispatch(DocumentLandingSdkBundleEvents::REFRESH_TOKEN_REQUEST, $event);
                }
            }
        }
        else {
	        $statusCode = 400;
        }

        $response = new JsonResponse($responseData);
        $response->setStatusCode($statusCode);

        return $response;
	}

    /**
     * @Route("/api/lead/create-or-update", name="create_or_update_lead")
     * @Method({"POST"})
     */
    public function createOrUpdateLeadAction(Request $request)
    {
        $this->isAuthenticated($request);
        $remoteTest = $request->query->get('test_webhook');
        $testPopulatedLead = $request->query->get('test_populated_lead');
        
        // For the present, the test from Document Landing only ensures that this endpoint is functional.
        // Passing a fake-but-valid Lead object isn't presently a priority.
        // If/When that changes, Document Landing will include a test_populated_lead=1 query parameter.
        // Further in this method, code is included to prevent such a development from saving test data.
        if ($remoteTest && !$testPopulatedLead) {
            return new JsonResponse(array(
                'test' => array(
                    'id' => 'createOrUpdateLead',
                    'success' => true
                )
            ));
        }
                
        $sdkManager = $this->container->get('documentlanding.sdk_manager');
        $leadClass = $sdkManager->getLeadClass();
        $dispatcher = $this->container->get('event_dispatcher');
        
        $entityManager = $this->container->get('doctrine')->getManager();
        $repository = $entityManager->getRepository($leadClass);
        $accessor = PropertyAccess::createPropertyAccessor();
        $lead = new $leadClass();

        $data = array();
        $content = $request->getContent();
    
        if (!empty($content)) {
            $data = json_decode($content, true);
        }

        foreach ($data['Lead'] as $key=>$value) {
            if ($accessor->isWritable($lead, $key)) {
                $accessor->setValue($lead, $key, $value);
            }
        }

        if (!isset($data['id'])){

            $missingRequired = false;
            $fieldMappings = $this->getFieldMappings($leadClass);

            foreach ($fieldMappings as $key=>$value){
                if ($key == 'id') {
                    continue;
                }
                if (isset($value['nullable']) && $value['nullable'] === false) {
                    if (!$accessor->getValue($lead, $key)) {
                        $missingRequired = true;
                        break;
                    }
                }
            }
            if (!$missingRequired) {
                $event = new LoadLeadEvent($request);
                $dispatcher->dispatch(DocumentLandingSdkBundleEvents::LOAD_LEAD, $event);
                $existingLead = $this->loadLeadFromSearchCriteria($event, $data);
                if ($existingLead) {
                    $data['id'] = $existingLead->getId();
                }
                else {              
                    if (!$remoteTest) {
                        $entityManager->persist($lead);
                        $entityManager->flush();
                    }
                    $data['id'] = $lead->getId();
                    $event = new NewLeadEvent($lead, $data);
                    $dispatcher->dispatch(DocumentLandingSdkBundleEvents::NEW_LEAD, $event);
                }    
            }
            else {
                $data['error'] = 'MISSING REQUIRED FIELDS';
            }
        }

        if (isset($data['id'])){
            $existingLead = $repository->findOneById($data['id']);
            if ($existingLead) {
                $lead = $existingLead;
            }
            else {
                // Presuming lead was removed locally that is still being tracked by Document Landing.
                // Don't remove local data.  Use IsDeleted or similar.
                return new JsonResponse($data);
            }

            // Merge.
            foreach ($data['Lead'] as $key=>$value) {
                if (empty($value)) {
                    continue;
                }
                if ($accessor->isWritable($lead, $key)) {
                    $accessor->setValue($lead, $key, $value);
                }
            }

            if (!$remoteTest) {
                $entityManager->persist($lead);
                $entityManager->flush();
            }
            $event = new UpdatedLeadEvent($lead, $data);
            $dispatcher->dispatch(DocumentLandingSdkBundleEvents::UPDATED_LEAD, $event);
        }

        return new JsonResponse($data);
    }

    /**
     * @Route("/api/lead/load", name="load_lead")
     * @Method({"POST"})
     */
    public function loadLeadAction(Request $request)
    {

        $this->isAuthenticated($request);
        $response = array();
        $remoteTest = $request->query->get('test_webhook');

        if ($remoteTest) {
            return new JsonResponse(array(
                'test' => array(
                    'id' => 'loadLead',
                    'success' => true
                )
            ));
        }

        $data = array();
        $content = $request->getContent();
    
        if (!empty($content)) {
            $data = json_decode($content, true);
        }

        $dispatcher = $this->container->get('event_dispatcher');
        $event = new LoadLeadEvent($request);
        $dispatcher->dispatch(DocumentLandingSdkBundleEvents::LOAD_LEAD, $event);
        $lead = $this->loadLeadFromSearchCriteria($event, $data);
        if ($lead) {
            $response['id'] = $lead->getId();
        }
        if (isset($response['id'])){
            $accessor = PropertyAccess::createPropertyAccessor();
            $response = $this->convertLeadToArray($lead, $accessor);
        }
        return new JsonResponse($response);
    }

    /**
     * @Route("/api/lead/fields", name="get_lead_fields")
     * @Method({"GET"})
     */
    public function loadFieldsAction(Request $request)
    {
        $this->isAuthenticated($request);
        
        $sdkManager = $this->container->get('documentlanding.sdk_manager');
        $leadClass = $sdkManager->getLeadClass();
        $fieldConstraints = $this->getConstraints($leadClass);
        $fields = array();
        $lead = new $leadClass();
        $props = $this->getLeadProperties($lead);

        $accessor = PropertyAccess::createPropertyAccessor();
        
        foreach($props as $prop) {

            $name = $prop->getName();

            $field = array();   
            $field['name'] = $name;
            $field['type'] = null;
            
            $nameMetadata = $this->getFieldMappingsByName($leadClass, $name);

            $field['length'] = $nameMetadata['length'];
            $field['nillable'] = ($nameMetadata['nullable'] ? 1 : 0);
            $field_type = $nameMetadata['type'];

            $field['type'] = $field_type;
            
            switch($field_type){
                case "integer":
                    $field['type'] = 'number';
                    break;
                case "string":
                    if ($field['length'] > 60) {
                        $field['type'] = 'textarea';
                    }
                    else {
                        $field['type'] = 'string';
                    }
                    break;
                case "boolean":
                    $field['type'] = 'boolean';
                    $field['length'] = 0;
                    break;
            }

            if (isset($fieldConstraints[$name])) {
                $a = $fieldConstraints[$name];
                if (isset($a['Email'])) {
                    $field['type'] = 'email';
                }
                elseif (isset($a['Choice'])) {
                    $options = array();
                    $choice = $a['Choice'];

                    $labelIsValue = true;
                    if ($this->is_assoc($choice->choices)) {
                        $labelIsValue = false;
                    }
                    foreach($choice->choices as $option_val=>$option_label) {
                        $options[] = array(
                            'active' => 1,
                            'defaultValue' => '', // Unsupported (8/4/2015) 
                            'label' => $option_label,
                            'value' => ($labelIsValue ? $option_label : $option_val)
                        );
                    }
                    $field['picklistValues'] = $options;
                    $field['type'] = ($choice->multiple ? 'multipicklist' : 'picklist');
                }
            }

            if (!$field['type']) {
                continue;
            }
            
            
            
            $field['label'] = $this->getLabel($name);

            $defaultValue = $accessor->getValue($lead, $name);
            $field['defaultValue'] = ($defaultValue ? $defaultValue : '');

            $fields[] = $field;
            
        }

        return new JsonResponse(array('fields' => $fields));

    }

    /**
     * @Route("/api/lead/schema", name="set_lead_schema")
     * @Method({"POST"})
     *
     * Changing a field label is supported.
     * Changing a field name is not supported. The expected result is truncated column.
     */
    public function setLeadSchemaAction(Request $request)
    {
        $this->isAuthenticated($request);
        
        $content = $request->getContent();
        
        if (!empty($content)) {
            $data = json_decode($content, true);
        }
        
        $event = new PreUpdateSchemaEvent($request);
        $dispatcher->dispatch(DocumentLandingSdkBundleEvents::PRE_UPDATE_SCHEMA, $event);           

        if ($event->getError() !== null) {
            $error = $event->getError();
            return $this->setLeadSchemaError($request, $error);
        }

        $sdkManager = $this->container->get('documentlanding.sdk_manager');
        $result = $sdkManager->setLeadSchema($data);

        return new JsonResponse($result);

    }

    /**
     * Put together the validations for the Lead Entity.
     */
    private function getConstraints($class)
    {
        $validations = array();

        $metadata = $this->container
                 ->get('validator')
                 ->getMetadataFactory()
                 ->getMetadataFor($class);

        $constrainedProperties = $metadata->getConstrainedProperties();
        
        foreach($constrainedProperties as $constrainedProperty)
        {
            $propertyMetadata = $metadata->getPropertyMetadata($constrainedProperty);
            $constraints = $propertyMetadata[0]->constraints;
            $outputConstraintsCollection=[];
            foreach($constraints as $constraint)
            {
                $class = new \ReflectionObject($constraint);
                $constraintName = $class->getShortName();
                $constraintParameter = null;

                switch ($constraintName) 
                {
//                    case "NotBlank":
                    case "Choice":
                        $outputConstraintsCollection[$constraintName] = $constraint;
                        break;
                }
            }
            $validations[$constrainedProperty] = $outputConstraintsCollection;
        }
        return $validations;
    }
    
    private function getFieldMappings($class) {
        if (!$this->entityMetadata) {
            $this->entityMetadata = $this->container->get('doctrine')->getEntityManager()->getClassMetadata($class);
        }
        return $this->entityMetadata->fieldMappings;
    }
    
    private function getFieldMappingsByName($class, $name)
    {
        $fieldMappings = $this->getFieldMappings($class);
        return $fieldMappings[$name];
    }
    
    private function splitCamelCase($input)
    {
        $array = preg_split(
            '/(^[^A-Z]+|[A-Z][^A-Z]+)/',
            $input,
            -1, /* no limit for replacement count */
            PREG_SPLIT_NO_EMPTY /*don't return empty elements*/
                | PREG_SPLIT_DELIM_CAPTURE /*don't strip anything from output array*/
        );
        return implode(' ', $array);
    }

    private function loadLeadFromSearchCriteria(LoadLeadEvent $event, $data = null)
    {
        $lead = null;
        $sdkManager = $this->container->get('documentlanding.sdk_manager');
        $leadClass = $sdkManager->getLeadClass();
        $entityManager = $this->container->get('doctrine')->getEntityManager();
        $repository = $entityManager->getRepository($leadClass);
        $searchCriteria = $event->getSearchCriteria();
        if (!$searchCriteria && isset($data) && isset($data['Lead']) && isset($data['Lead']['Email'])) {
            $searchCriteria = array(
                'Email' => $data['Lead']['Email']
            );
        }
        if ($searchCriteria) {
            $lead = $repository->findOneBy($searchCriteria);
        }
        return $lead;
    }

    private function convertLeadToArray($lead, $accessor)
    {
        $props = $this->getLeadProperties($lead);
        $leadAsArray = array();
        foreach($props as $prop) {
            $name = $prop->getName();
            $leadAsArray[$name] = $accessor->getValue($lead, $name);
        }
        return $leadAsArray;
    }

    private function getLeadProperties($lead)
    {
        $reflect = new \ReflectionClass($lead);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED);
        return $props;
    }
    
    private function getLabel($name)
    {
        $sdkManager = $this->container->get('documentlanding.sdk_manager');
        $translationId = 'lead.' . $name;
        $label = $this->container->get('translator')->trans($translationId, array(), $sdkManager->getBundleName());
        if ($label == $translationId) {
            $label = $this->splitCamelCase($name);
        }
        return $label;
    }
    
    private function is_assoc(array $array) {
        return (bool)count(array_filter(array_keys($array), 'is_string'));
    }

    /**
     * Dispatch API_REQUEST in case implementation has additional requirements.
     * The foremost additional contraint would be Document Landing IP Range(s).
     *
     * Users of ApiRequestEvent should be aware of the test_webhook=1 querystring parameter.
     * It is passed by Document Landing when the webhooks are being setup.
     */
    private function isAuthenticated(Request $request, $data = null)
    {
        $event = new ApiRequestEvent($request);
        $config = $this->container->getParameter('DocumentLandingSdkBundleConfig');
        $authorizationHeader = $request->headers->get('Authorization');

        if (!$authorizationHeader) {
            $event->setIsValid(false);
        }
        else {
	        // See DocumentLandingSdkBundleEvents.php for more about this.
	        if ($config['access_token']) {
		        if ($authorizationHeader != 'Bearer ' . $config['access_token']) {
		            $event->setIsValid(false);
	            }
	        }
        }
        
        $dispatcher = $this->container->get('event_dispatcher');
        $dispatcher->dispatch(DocumentLandingSdkBundleEvents::API_REQUEST, $event);
        if (!$event->getIsValid()) {
            throw new HttpException(401, "INVALID_SESSION_ID", "Session expired or invalid");
        }
    }

}
