CREATE TABLE question (
    id VARCHAR(32) NOT NULL UNIQUE,
    text TEXT,
    created_by TEXT NOT NULL,
    seq INTEGER PRIMARY KEY
);
CREATE UNIQUE INDEX by_seq ON question (
    seq DESC
);

CREATE TABLE question_cat (
    q VARCHAR(32) NOT NULL,
    cat VARCHAR(32)
);
CREATE INDEX q_cat ON question_cat (
    q ASC
);
CREATE INDEX q_cat_cat ON question_cat (
    cat
);

CREATE TABLE answer (
    id VARCHAR(32) NOT NULL,
    q VARCHAR(32) NOT NULL,
    text TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(32) NULL
);
CREATE INDEX a_q ON answer (
    q ASC
);
CREATE INDEX a_created_by ON answer (
    created_by
);

CREATE TABLE vote (
    a VARCHAR(32) NOT NULL,
    q VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(32) NULL
);
CREATE INDEX v_q ON vote (
    q ASC
);
CREATE INDEX v_by ON vote (
    created_by ASC
);
