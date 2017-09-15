<?php

namespace AppBundle\Controller\Api;

use AppBundle\Extension\ApiResponse;
use AppBundle\Extension\EditorExtension;
use AppBundle\Extension\PythonToolsExtension;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class NgramController extends ApiController
{

    /**
     * @Route("/ngram/spin", name="api_ngram_spin")
     * @Method("GET")
     */
    public function spinContent(Request $request)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        $data = json_decode($request->getContent(), true);
        /*
         * {
         * "text": {}
         * "frame_size": 3,
         * "frame_peek_probability":0.8
         * }
         */
        $ext = new PythonToolsExtension($this, $username);
        $text = '2 In connection with this, the question of acceptable tolerance is equally significant to the evaluation of the performance of the basic nausea/vomiting healing. Notwithstanding the marked benefits of miscellaneous central nervous system agents, that as high similarity to H1 histamine receptors, insurmountable fixation to them and, as a result, quick onset of action, a sufficient lasting of antihistamine action, lack of obstruction of any other groups of sense organs and tachyphylaxis impact, meanwhile, individual pharmacy products of miscellaneous central nervous system agents may pass through the blood-brain barrier and block the histamine receptors of the cerebrum. Based on the study taken in 2017 named quality implies the risk of growth of a calmative impact, including affect the cognitive skills of the sick person, such as feelings, behavior, memory and brainwork. at a dosage of two mg once daily, and the 2nd team was made up of 907 ill individuals which used daily 22 mg once. It must be declared that in those two groups the share of sick persons having different types of risk conditions was similar. Nevertheless, the assessment of the effectiveness of therapy illustrated that in the exceptional majority of sick persons the treatment has been active and complications wasnʼt visible. At the same moment with the studies which disclosed the effectiveness of medication, we considered the impact of the subject in question such as trouble sitting still. Consequently, the given data reveal that the peculiarities of the in-patient course of nausea/vomiting, postoperative have a unfavorable response in in terms of sequela, worsening the state of health, functionality, spirit, attention, including particularly reducing their capability to get rid of symptoms. Experts of the Institute of Allergy, Asthma and Immunology, Hanzhong (China), suggested that the assignment of effective basic healing to patients with nausea/vomiting, postoperative together with the improvement of the in-patient diagnoses of the health issue sickness will cause distinctive modifications in the mental area of individuals. Nevertheless, within the checkup of recipes consumers who administered second-generation antihistamines to heal the main evidence of nausea/vomiting, postoperative, it has been discovered that medicating droperidol had no significant influence on the patients well-being, that was equally worsened in every seven of 45 patients. Notably amongst patients applying droperidol, just 12 % of prescriptions consumers faced worsening of their state of robustness. In general, scientists detected identical progress in the evaluation of functionality, when a reduction in mentioned indicator was seen in 41 % of patients taking droperidol, and in fifty eight % of patients not accepting treatment. An assessment of the action of assigned therapy on the spirit of ill people with the consideration of possible associated medical issue sickness and all different potential diseases revealed that out of patients applying droperidol, temper exacerbating has been found twice more often versus the team not accepting treatment and was sixty nine %, while amongst patients administering droperidol, a decrease in this data has been found only by seventeen %. Consequently, according to the results accessed in the study in Basaksehir (Turkey), the following statements can be made:';
        $frame_size = 3;
        $frame_ppb = 0.8;

        $ret = $ext->transformTextNGMC($text, $frame_size, $frame_ppb);
        return ApiResponse::resultValue($ret);
    }

    /**
     * @Route("/ngram/spin", name="api_ngram_spin_post")
     *
     * @Method ("POST")
     */
    public function spinContentPost(Request $request)
    {
        $username = $this->getUser()->getUsernameCanonical();
        if ($username == null) {
            return ApiResponse::resultUnauthorized();
        }

        $data = json_decode($request->getContent(), true);
        /*
         * {
         * "text": {}
         * "frame_size": 3,
         * "frame_peek_probability":0.8
         * }
         */
        $ext = new PythonToolsExtension($this, $username);
        $text = $data['text'];
        $frame_size = $data['frame_size'];
        $frame_ppb = $data['frame_peek_probability'];
        $mode = $data['mode'];


        $ret = $ext->transformTextNGMC($text, $frame_size, $frame_ppb, $mode);

        return ApiResponse::resultValue($ret);
    }
}
