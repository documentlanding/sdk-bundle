<?php
	
namespace DocumentLanding\SdkBundle\Service;

use DocumentLanding\SdkBundle\DocumentLandingSdkBundleEvents;
use DocumentLanding\SdkBundle\Events\PostUpdateSchemaEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SdkManager
{

    protected $config;
    protected $eventDispatcher;
    protected $leadClass;
    protected $bundleName;
    protected $rootDir;
    protected $bundles;
    protected $env;

    public function __construct($SdkBundleConfig, EventDispatcherInterface $eventDispatcher, $bundles, $rootDir, $env, RouterInterface $router)
    {
	    $this->config = $SdkBundleConfig;
	    $this->eventDispatcher = $eventDispatcher;
	    $this->bundles = $bundles;
	    $this->rootDir = $rootDir;
	    $this->env = $env;
	    $this->router = $router;
    }

    public function getLeadClass()
    {
	    if ($this->leadClass) {
		    return $this->leadClass;
	    }
	    if (!$this->config['lead_class']) {
		    $this->leadClass = 'DocumentLanding\SdkBundle\Entity\Lead';
            if (!class_exists($this->leadClass)) {
	            $this->setLeadSchema(null);
            }
	    }
	    else {
		    $this->leadClass = $this->config['lead_class'];
	    }
	    return $this->leadClass;
    }

    public function setLeadSchema($data = null)
    {

	    if (!$data) {

            $array = array(
                'lead' => array(
                    'src' => __DIR__ . '/../Resources/init/Lead.orm.yml',
                    'dst' => __DIR__ . '/../Resources/config/doctrine/Lead.orm.yml',
            	),
            );
            
            $entity_dir = __DIR__ . '/../Entity';
//            if (!is_dir($entity_dir) && !is_link($entity_dir)) {
//                mkdir($entity_dir, 0775, true);
//            }
            
            foreach ($array as $key=>$value) {
                $src_dir = dirname($value['src']);
                $dst_dir = dirname($value['dst']);
//                if (!is_dir($src_dir)) {
//                    mkdir($src_dir, 0775, true);
//                }
//                if (!is_dir($dst_dir)) {
//                    mkdir($dst_dir, 0775, true);
//                }
                if (!copy($value['src'], $value['dst'])) {
                    return array('success' => false, 'error' => 'Failed to write Lead Entity and/or metadata files.');
                }
            }

		    $yaml = new Parser();
		    $array = $yaml->parse(file_get_contents(__DIR__ . '/../Resources/config/doctrine/Lead.orm.yml'));
		    $data = $array['SdkBundle\Entity\Lead'];

	    }


	    $fieldsArray                 = array();
	    $fieldChoiceArray            = array();
	    $translationArray            = array();
        $translationArray['choices'] = array();

        $prePersistPhp = '';
        $preUpdatePhp = '';

        $isSalesforce = false;
        $privateProperties = array();
        $defaultLength = 60;

        if (isset($data['urlDetail']) && strpos($data['urlDetail'], 'salesforce.com') !== false) {
	        $isSalesforce = true;
	        $privateProperties = $this->getSalesforcePrivateLeadFields();
	        // Set the CreatedAt etc.
        }
	    if (isset($data['fields']) && is_array($data['fields'])) {
		    foreach($data['fields'] as $name=>$field) {
			    if (isset($field['nillable'])) {
				    $field['nullable'] = $field['nillable'];
			    }
			    elseif (!isset($field['nullable'])) {
				    $field['nullable'] = true;
			    }
			    if (!isset($field['property'])) {
                    if (isset($privateProperties[$name])) {
                        $field['property'] = 'private';
                    }
                    else {
	                    $field['property'] = 'protected';
                    }
			    }
			    if (!isset($field['length'])) {
				    $field['length'] = $defaultLength;
			    }

			    $fieldsArray[$name] = array(
				    'type' => (in_array($field['type'], array('string', 'text', 'boolean', 'datetime')) ? $field['type'] : 'string'),
				    'length' => (is_numeric($field['length']) ? $field['length'] : 60),
				    'nullable' => ($field['nullable'] ? true : false),
				    'property' => (in_array($field['property'], array('public', 'protected')) ? $field['property'] : 'private'),
			    );
			    if (isset($field['label'])) {
				    $translationArray[$name] = $field['label'];
			    }
			    if (isset($field['picklistValues']) && is_array($field['picklistValues'])) {
				    $choiceArray = array();
				    foreach($field['picklistValues'] as $key=>$value){
					    if (isset($value['active'])) {
						    if ($value['active'] == 0) {
							    continue;
						    }
					    }
                        if (!isset($value['value']) || !isset($value['label'])) {
	                        continue;
                        }
                        $choiceArray[$value['value']] = $value['label']; // $value['value']
				    }

                    $fieldChoiceArray[$name] = array(
                        array(
                            'Choice' => array(
                                'choices' => $choiceArray
                            )
                        )
                    );
			    }
			    if (isset($field['default'])) {
				    if ($field['type'] == 'datetime') {
					    if ($field['default'] == 'CREATED_DATETIME') {
						    $prePersistPhp .= '$this->' . $name . ' = new \DateTime("now"); ';
					    }
					    if ($field['default'] == 'UPDATED_DATETIME') {
						    $preUpdatePhp .= '$this->' . $name . ' = new \DateTime("now"); ';
					    }
				    }
				}
		    }
	    }
	    else {
		    return array('success' => false, 'error' => 'Field array missing.');
	    }

	    $bundleName = $this->getBundleName();

	    if ($bundleName != 'SdkBundle') {
		    return array('success' => false, 'error' => 'SDK configured to use static lead class.');
	    }

	    $leadClass = 'DocumentLanding\SdkBundle\Entity\Lead';
        $dumper = new Dumper();
        
        // Ensure implementation of DocumentLanding\SdkBundle\Model\LeadInterface

        if (!isset($fieldsArray['Email'])) {
	        $fieldsArray['Email'] = array(
			    'type' => 'string',
			    'length' => 255,
			    'nullable' => false,
			    'property' => 'public',
			);
        }

        if (!isset($fieldsArray['LeadSource'])) {
	        $fieldsArray['LeadSource'] = array(
			    'type' => 'string',
			    'length' => 255,
			    'nullable' => true,
			    'property' => 'protected',
			);
        }

        if (!isset($fieldsArray['Description'])) {
	        $fieldsArray['Description'] = array(
			    'type' => 'string',
			    'length' => 255,
			    'nullable' => true,
			    'property' => 'private',
			);
        }

        // Entity
        // Lifecycle callbacks in the YAML easiest method to generate relavent assertion annotation at top of class.
        // These lifecycle methods themselves do nothing.

	    $entityArray = array(
		    $leadClass => array(
			    'type' => 'entity',
			    'table' => 'lead',
                'id' => array('id' => array('type' => 'integer', 'generator' => array('strategy' => 'AUTO'))),
                'fields' => $fieldsArray,
                'lifecycleCallbacks' => array(
	                'prePersist' => array( 'onPrePersist' ),
	                'preUpdate' => array( 'onPreUpdate' ),
                )
		    )
        );


        // Create File

        $yaml = $dumper->dump($entityArray, 5);
        file_put_contents(__DIR__ . '/../Resources/config/doctrine/Lead.orm.yml', $yaml);


	    // Choices

	    $validation_array = array(
		    $leadClass => array(
                'properties' => $fieldChoiceArray
		    )
        );

        $yaml = $dumper->dump($validation_array, 6);
        file_put_contents(__DIR__ . '/../Resources/config/validation.yml', $yaml);


	    // Translation
	    
	    $translationArray = array(
		    'lead' => $translationArray
        );

        $yaml = $dumper->dump($translationArray, 2);
        file_put_contents(__DIR__ . '/../Resources/translations/SdkBundle.en.yml', $yaml);


	    // Clear Cache
	    
	    $consolePath = $this->rootDir . '/console';
	    $base = PHP_BINDIR . '/php ' . $consolePath . ' ';
	    $env = $this->env;

//        $command = $base . 'cache:clear --env=' . $env;
//        $process = new Process($command);
//        $process->run();
//        if (!$process->isSuccessful()) {
//	        return array('success' => false, 'error' => $process->getErrorOutput());
//        }
  
 

	    // Generate Entity

        $command = $base . 'cache:clear --env=' . $env;
        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
	        return array('success' => false, 'error' => $process->getErrorOutput());
        }

        $command = $base . 'generate:doctrine:entities DocumentLanding/' . $this->getBundleName() . ' --path ' . $this->rootDir . '/Resources';
        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
	        return array('success' => false, 'error' => $process->getErrorOutput());
        }

	    // Modify Generated Entity
	    //   Switch certain private properties to public/protected
	    //   Add LifeCycle callbacks for default values (created, updated, etc)
	    //   Ensure implements LeadInterface
	    
	    $classContent = file_get_contents(__DIR__ . '/../Entity/Lead.php');
        
        foreach($fieldsArray as $name=>$field){
	        if (isset($field['property']) && $field['property'] != 'private') {
		        $classContent = str_replace('private $' . $name . ';', $field['property'] . ' $' . $name . ';', $classContent);
	        }
        }
        
        $pos = strrpos($classContent, 'onPrePersistAppend');
        
        if (!$pos) {

	        $pos = strrpos($classContent, '}');
            $classContent = substr_replace($classContent, '', $pos, strlen('}'));
            $classContent .= '

    /**
     * @ORM\PrePersist
     */
    public function onPrePersistAppend()
    {
        ' . $prePersistPhp . '
    }

    /** 
     * @ORM\PreUpdate 
     */  
    public function onPreUpdateAppend()  
    {  
        ' . $preUpdatePhp . '
    }

}
';
        }
        
        $pos = strrpos($classContent, 'implements \DocumentLanding\SdkBundle\Model\LeadInterface');

        if (!$pos) {
	        $classContent = str_replace('class Lead', 'class Lead implements \DocumentLanding\SdkBundle\Model\LeadInterface', $classContent);
	    }

        file_put_contents(__DIR__ . '/../Entity/Lead.php', $classContent);


	    // Autoload won't be aware of the Lead Entity class until the next page load.
	    // So jam it into the current run-time.
	    // Otherwise $this->getConstraint(...); fails further down this thread.
	    include_once __DIR__ . '/../Entity/Lead.php';
	    

	    // Update Schema       
        
        $command = $base . 'doctrine:schema:update --force';
        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            return array('success' => false, 'error' => $process->getErrorOutput());
        }

        $event = new PostUpdateSchemaEvent($leadClass);
        $this->eventDispatcher->dispatch(DocumentLandingSdkBundleEvents::POST_UPDATE_SCHEMA, $event);
        
        return array('success' => true);

	}

    public function getBundleName()
    {
	    if ($this->bundleName) {
		    return $this->bundleName;
	    }
	    
	    if (!$this->config['lead_class']) {
		    $this->bundleName = 'SdkBundle';
		    return $this->bundleName;
	    }

        $entityClass = $this->config['lead_class'];
        $bundles = $this->bundles;
    
        foreach($bundles as $name=>$bundleClass){
            $matchEntityClass = substr($entityClass,0,strpos($entityClass,'\\Entity\\'));
            if(strpos($bundleClass,$matchEntityClass) === false){
                // Must deal with strpos ambiguity one way or another...
            }
            else {
                $this->bundleName = $name;
                break;
            }
        }
        
        return $this->bundleName;
    }

    /**
	 * Salesforce Fields not (directly) used by Document Landing Smart Gates.
	 */
    public function getSalesforcePrivateLeadFields()
    {

        $private = array();
        $private['Id'] = 1;
        $private['IsDeleted'] = 1;            
        $private['MasterRecordId'] = 1;
        $private['LeadSource'] = 1;
        $private['Status'] = 1;        
        $private['Rating'] = 1;
        $private['OwnerId'] = 1;
        $private['IsConverted'] = 1;
        $private['ConvertedDate'] = 1;
        $private['ConvertedAccountId'] = 1;
        $private['ConvertedContactId'] = 1;
        $private['ConvertedOpportunityId'] = 1;
        $private['IsUnreadByOwner'] = 1;
        $private['CreatedDate'] = 1;
        $private['CreatedById'] = 1;
        $private['LastModifiedDate'] = 1;
        $private['LastModifiedById'] = 1;            
        $private['SystemModstamp'] = 1;
        $private['LastActivityDate'] = 1;
        $private['Jigsaw'] = 1;
        $private['JigsawContactId'] = 1;
        $private['EmailBouncedReason'] = 1;
        $private['EmailBouncedDate'] = 1;
        
        return $private;
    }


    /*
	 * Two reasons for this page.
	 * One, welcome! It all worked out. You are a-ok.
	 * Two, new Constraint Metadata is missing until refresh.
	 */
    public function onUrlParse(GetResponseEvent $event)
    {
	    if (!$this->config['lead_class']) {
		    $this->leadClass = 'DocumentLanding\SdkBundle\Entity\Lead';
            if (!class_exists($this->leadClass)) {
	            $this->setLeadSchema(null);
	            $response = new RedirectResponse($this->router->generate('config_success', array(), true));
                $event->setResponse($response);
            }
	    }
    }


}