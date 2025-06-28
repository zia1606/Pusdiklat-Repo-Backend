import json
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import re
import nltk
from nltk.tokenize import word_tokenize
from nltk.corpus import stopwords
from nltk.stem import PorterStemmer
import sys

# Download NLTK resources
nltk.download('punkt')
nltk.download('stopwords')

def preprocess_text(text):
    # Case folding
    text = text.lower()
    
    # Tokenization
    tokens = word_tokenize(text)
    
    # Stopword removal
    stop_words = set(stopwords.words('indonesian'))
    tokens = [word for word in tokens if word not in stop_words]
    
    # Remove special characters
    tokens = [re.sub(r'[^\w\s]', '', word) for word in tokens]
    
    # Stemming
    stemmer = PorterStemmer()
    tokens = [stemmer.stem(word) for word in tokens]
    
    return ' '.join(tokens)

def generate_recommendations(documents_json, current_id, limit=5):
    # Load documents
    documents = json.loads(documents_json)
    df = pd.DataFrame(documents)
    
    # Preprocess text (title + keywords)
    df['processed_text'] = (df['judul'].fillna('') + ' ' + df['keywords'].fillna('')).apply(preprocess_text)
    
    # Create TF-IDF matrix
    vectorizer = TfidfVectorizer()
    tfidf_matrix = vectorizer.fit_transform(df['processed_text'])
    
    # Find current document index
    current_idx = df[df['id'] == current_id].index[0]
    
    # Calculate cosine similarities
    cosine_sim = cosine_similarity(tfidf_matrix[current_idx], tfidf_matrix)
    
    # Get similarity scores
    sim_scores = list(enumerate(cosine_sim[0]))
    sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)
    
    # Get top recommendations (excluding itself)
    recommendations = []
    for idx, score in sim_scores[1:limit+1]:
        recommendations.append({
            'id': int(df.iloc[idx]['id']),
            'similarity_score': float(score),
            'judul': df.iloc[idx]['judul'],  # Tambahkan field judul
            'keywords': df.iloc[idx]['keywords']  # Tambahkan field keywords
        })
    
    # Urutkan lagi berdasarkan score untuk memastikan
    recommendations = sorted(recommendations, key=lambda x: x['similarity_score'], reverse=True)
    return recommendations[:limit]  # Pastikan tidak melebihi limit

if __name__ == "__main__":
    if len(sys.argv) > 2:
        documents_json = sys.argv[1]
        current_id = int(sys.argv[2])
        limit = int(sys.argv[3]) if len(sys.argv) > 3 else 5
        recommendations = generate_recommendations(documents_json, current_id, limit)
        print(json.dumps(recommendations))