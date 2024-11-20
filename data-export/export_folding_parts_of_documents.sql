SELECT
  steps.bundle as step_bundle,
    steps.entity_id,
    steps.delta,
    steps.os2loop_documents_steps_target_id as step_target_id,
    step_title.os2loop_documents_step_title_value as step_title,
    -- step_text.bundle as text_bundle,
    step_text.delta as text_delta,
    step_text.os2loop_documents_step_text_value,
    -- step_text.os2loop_documents_step_text_format,
    -- step_image.bundle as image_bundle,
  step_image.delta as image_delta,
  step_image.uri,
  step_image.filemime,
  step_image.filesize,
    DATE_FORMAT(FROM_UNIXTIME(step_image.created), '%Y-%m-%dT%H:%i:%s') as created,
    DATE_FORMAT(FROM_UNIXTIME(step_image.`changed`), '%Y-%m-%dT%H:%i:%s') as `changed`
from paragraph__os2loop_documents_steps as steps
left join paragraph__os2loop_documents_step_title as step_title on steps.os2loop_documents_steps_target_id = step_title.entity_id -- contains 682 records, but only  617 records are join, so there might be some problem
left join paragraph__os2loop_documents_step_text as step_text on steps.os2loop_documents_steps_target_id = step_text.entity_id -- bundle are only os2loop_documents_step, contains 620 reconds, but only 558 are joined
left join (
  WITH media_attachments AS (SELECT
    media_collected.mid,
    fm.uri, -- where public:// -> https://loop.sundhedogomsorg.dk/sites/loop.sundhedogomsorg.dk/files/
    fm.filemime,
    fm.filesize,
    fm.created,
    fm.changed
  FROM (SELECT
      m_fd.mid,
      -- IFNULL (m_mf.field_media_file_target_id, IFNULL(m_mi.field_media_image_target_id, m_ml.field_media_library_target_id)) as target_id
            IFNULL (m_mf.field_media_file_target_id, m_mi.field_media_image_target_id) as target_id
    from media_field_data as m_fd
    left join media__field_media_file as m_mf on m_fd.mid = m_mf.entity_id -- remember dep on m_fd.bundle whether it should be media__field_media_file, media__field_media_image or media__field_media_library we need to join on
    left join media__field_media_image as m_mi on m_fd.mid = m_mi.entity_id
    -- left join media__field_media_library as m_ml on m_fd.mid = m_ml.entity_id
        ) as media_collected
  left join file_managed as fm on media_collected.target_id = fm.fid)
  SELECT
    step_image.bundle,
        -- step_image.deleted,
        step_image.entity_id,
        -- step_image.revision_id,
        -- step_image.langcode,
        step_image.delta,
        -- step_image.os2loop_documents_step_image_target_id,
    media_attachments.uri,
    media_attachments.filemime,
    media_attachments.filesize,
    media_attachments.created,
    media_attachments.`changed`
  FROM paragraph__os2loop_documents_step_image as step_image
  left join media_attachments on step_image.os2loop_documents_step_image_target_id = media_attachments.mid
    ) as step_image on steps.os2loop_documents_steps_target_id = step_image.entity_id
