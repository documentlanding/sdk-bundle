<?php
    
namespace DocumentLanding\SdkBundle\Model;

interface LeadInterface
{
    /**
     * Gets id.
     *
     * @return string
     */
    public function getId();

    /**
     * Sets the email.
     *
     * @param string $email
     *
     * @return void
     */
    public function setEmail($email);

    /**
     * Gets the email.
     *
     * @return string
     */
    public function getEmail();
    
    /**
     * Sets the leadSource.
     *
     * @param string $leadSource
     *
     * @return void
     */
    public function setLeadSource($leadSource);

    /**
     * Gets the leadSource.
     *
     * @return string
     */
    public function getLeadSource();    

    /**
     * Sets the description.
     *
     * @param string $description
     *
     * @return void
     */
    public function setDescription($description);

    /**
     * Gets the description.
     *
     * @return string
     */
    public function getDescription(); 

}