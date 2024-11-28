WITH media_attachments AS (SELECT
	media_collected.mid,
	fm.uri, -- where public:// -> https://loop.sundhedogomsorg.dk/sites/loop.sundhedogomsorg.dk/files/
    fm.filemime, 
    fm.filesize, 
    fm.created, 
    fm.changed 
  FROM (SELECT 
		m_fd.mid,
		-- m_fd.vid,
		-- -- m_fd.bundle,
		-- -- m_fd.langcode,
		-- m_fd.status,
		-- m_fd.uid,
		-- m_fd.name,
		-- m_fd.thumbnail__target_id,
		-- m_fd.thumbnail__alt,
		-- m_fd.thumbnail__title,
		-- m_fd.thumbnail__width,
		-- m_fd.thumbnail__height,
		-- m_fd.created,
		-- m_fd.changed,
		-- m_fd.default_langcode,
		-- m_fd.revision_translation_affected,
		-- IFNULL (m_mf.bundle, IFNULL(m_mi.bundle, m_ml.bundle)) as bundle, -- where m_fd.bundle != IFNULL (m_mf.bundle, IFNULL(m_mi.bundle, m_ml.bundle)) returns zero mathes
		-- IFNULL (m_mf.deleted, IFNULL(m_mi.deleted, m_ml.deleted)) as deleted,
		-- IFNULL (m_mf.entity_id, IFNULL(m_mi.entity_id, m_ml.entity_id)) as entity_id,
		-- IFNULL (m_mf.revision_id, IFNULL(m_mi.revision_id, m_ml.revision_id)) as revision_id,
		-- IFNULL (m_mf.langcode, IFNULL(m_mi.langcode, m_ml.langcode)) as langcode,  -- where  m_fd.langcode != IFNULL (m_mf.langcode, IFNULL(m_mi.langcode, m_ml.langcode)) returns zero mathes
		-- IFNULL (m_mf.delta, IFNULL(m_mi.delta, m_ml.delta)) as delta,
		IFNULL (m_mf.field_media_file_target_id, IFNULL(m_mi.field_media_image_target_id, m_ml.field_media_library_target_id)) as target_id
		-- m_mf.field_media_file_display,
		-- m_mf.field_media_file_description,
		-- m_mi.field_media_image_alt,
		-- m_mi.field_media_image_title,
		-- m_mi.field_media_image_width,
		-- m_mi.field_media_image_height
	from media_field_data as m_fd
    left join media__field_media_file as m_mf on m_fd.mid = m_mf.entity_id -- remember dep on m_fd.bundle whether it should be media__field_media_file, media__field_media_image or media__field_media_library we need to join on
    left join media__field_media_image as m_mi on m_fd.mid = m_mi.entity_id
    left join media__field_media_library as m_ml on m_fd.mid = m_ml.entity_id) as media_collected
  left join file_managed as fm on media_collected.target_id = fm.fid),
question_attachments AS (SELECT
	n_qf.entity_id,
	media_attachments.uri, -- where public:// -> https://loop.sundhedogomsorg.dk/sites/loop.sundhedogomsorg.dk/files/
    media_attachments.filemime, 
    media_attachments.filesize, 
    media_attachments.created, 
    media_attachments.changed 
  FROM node__os2loop_question_file as n_qf
  left join media_attachments on n_qf.os2loop_question_file_target_id = media_attachments.mid),
answer_attachment AS (SELECT
	c_am.entity_id,
	media_attachments.uri, -- where public:// -> https://loop.sundhedogomsorg.dk/sites/loop.sundhedogomsorg.dk/files/
    media_attachments.filemime, 
    media_attachments.filesize, 
    media_attachments.created, 
    media_attachments.changed 
  FROM comment__os2loop_question_answer_media as c_am
  left join media_attachments on c_am.os2loop_question_answer_media_target_id = media_attachments.mid) -- the only entry here is null, maybe it has been deleted??, but in future there might be something

Select -- *,
  n_fd.title as title, 
  pa.alias as relative_public_url,
  n_qc.os2loop_question_content_value as question, 
  DATE_FORMAT(FROM_UNIXTIME(n_fd.created), '%Y-%m-%dT%H:%i:%s') as question_created,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.changed), '%Y-%m-%dT%H:%i:%s') as question_changed_in_some_way,
  c_qa.os2loop_question_answer_value as response,
  DATE_FORMAT(FROM_UNIXTIME(c_fd.created), '%Y-%m-%dT%H:%i:%s') as response_created,
  DATE_FORMAT(FROM_UNIXTIME(c_fd.changed), '%Y-%m-%dT%H:%i:%s') as response_changed_in_some_way,
  IF(c_qa.os2loop_question_answer_value LIKE '%loop.sundhedogomsorg%', 1, 0) as internal_reference,
  IF(c_qa.os2loop_question_answer_value LIKE '%http%' or c_qa.os2loop_question_answer_value LIKE '%href%' or c_qa.os2loop_question_answer_value LIKE '%www.%', 1, 0) as any_reference,
  u_jt.os2loop_user_job_title_value as questioner_job_title,
  subject_tt_fd.name as `subject`,
  agg_professions.target_professions as target_professions, 
  agg_tags.tags as question_tags,
  u_jt_c.os2loop_user_job_title_value as respondent_job_title,
  IFNULL(flags.redaktionens_anbefaling,0) as editor_choice, -- add if null -> 0 
  IFNULL(flags.likes,0) as likes, -- if null ->0
  -- (, editor_edited)
  -- # images/media: 
  --    table comment__os2loop_question_answer_media only contains one line and the media here I cannot dereference
  --    it seem that it should be referenced in the bundle os2loop_media_file (according to table media) corresponding to table media__field_media_file, but here the reference is not among the entity_ids
  --    The reference could also not be found in any of the others media__field's nor file_manage 
  question_attachments.uri as question_attachment_uri, -- where public:// -> https://loop.sundhedogomsorg.dk/sites/loop.sundhedogomsorg.dk/files/
  question_attachments.filemime as question_attachment_filemime, 
  question_attachments.filesize as question_attachment_filesize, 
  DATE_FORMAT(FROM_UNIXTIME(question_attachments.created), '%Y-%m-%dT%H:%i:%s') as question_attachment_created, 
  DATE_FORMAT(FROM_UNIXTIME(question_attachments.changed), '%Y-%m-%dT%H:%i:%s') as question_attachment_changed
  /* Not included as of export 2/11 - 2023 there are no attachments in answers
  answer_attachment.uri as answer_attachment_uri, -- where public:// -> https://loop.sundhedogomsorg.dk/sites/loop.sundhedogomsorg.dk/files/
  answer_attachment.filemime as answer_attachment_filemime, 
  answer_attachment.filesize as answer_attachment_filesize, 
  answer_attachment.created as answer_attachment_created, 
  answer_attachment.changed as answer_attachment_changed
  */
from (
  SELECT nid,vid,type,uid,title,created,changed 
  FROM node_field_data
  where type='os2loop_question'
    and status = 1
  ) as n_fd
left join path_alias as pa on CONCAT('/node/',n_fd.nid) = pa.path
left join node__os2loop_question_content as n_qc on n_fd.nid = n_qc.entity_id
left join user__os2loop_user_job_title as u_jt on n_fd.uid = u_jt.entity_id
left join node__os2loop_shared_subject as n_ss on n_fd.nid = n_ss.entity_id
left join taxonomy_term_field_data as subject_tt_fd on n_ss.os2loop_shared_subject_target_id = subject_tt_fd.tid
left join (SELECT
	n_sp.entity_id,
    JSON_ARRAYAGG(target_prof_tt_fd.name) as target_professions
  FROM node__os2loop_shared_profession as n_sp
  LEFT JOIN taxonomy_term_field_data as target_prof_tt_fd on n_sp.os2loop_shared_profession_target_id = target_prof_tt_fd.tid
  WHERE n_sp.bundle = 'os2loop_question'
  group by n_sp.entity_id) AS agg_professions on n_fd.nid = agg_professions.entity_id
left join (SELECT 
	n_st.entity_id,
    JSON_ARRAYAGG(tag_tt_fd.name) as tags
  FROM node__os2loop_shared_tags as n_st 
  left join taxonomy_term_field_data as tag_tt_fd on n_st.os2loop_shared_tags_target_id = tag_tt_fd.tid
  where n_st.bundle = 'os2loop_question'
  group by n_st.entity_id) as agg_tags on n_fd.nid = agg_tags.entity_id
left join question_attachments on n_fd.nid = question_attachments.entity_id
left join comment_field_data as c_fd on n_fd.nid = c_fd.entity_id
left join comment__os2loop_question_answer as c_qa on c_fd.cid = c_qa.entity_id
left join user__os2loop_user_job_title as u_jt_c on c_fd.uid = u_jt_c.entity_id
left join (SELECT 
	entity_id, 
    MAX( IF (flag_id = 'os2loop_upvote_correct_answer', True, False)) as redaktionens_anbefaling,
    MAX( IF (flag_id = 'os2loop_upvote_upvote_button', count, 0)) as likes
  FROM flag_counts
  where entity_type = 'comment' 
  group by entity_id
  -- maybe and flag_id = 'os2loop_upvote_correct_answer'
  ) as flags on flags.entity_id = c_fd.cid
left join answer_attachment on c_fd.cid = answer_attachment.entity_id
-- where 
-- and (c_qa.os2loop_question_answer_value LIKE '%http%' or c_qa.os2loop_question_answer_value LIKE '%href%')
-- where c_qa.os2loop_question_answer_value LIKE '%loop.sundhedogomsorg%'
 -- c_fd.cid = '5183'
 -- and n_fd.title Like 'Ved kommunikation %';