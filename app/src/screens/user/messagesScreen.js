import React, { useState, useEffect, useRef } from 'react';
import { 
    View, Text, StyleSheet, FlatList, TextInput, 
    TouchableOpacity, KeyboardAvoidingView, Platform,
    SafeAreaView, ActivityIndicator, Alert, StatusBar, Modal
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function MessagesScreen() {
    const [userId, setUserId] = useState(null); 
    const [loading, setLoading] = useState(true);
    const [vets, setVets] = useState([]);
    const [selectedVet, setSelectedVet] = useState(null);
    const [messages, setMessages] = useState([]);
    const [inputText, setInputText] = useState('');
    const [showVetList, setShowVetList] = useState(false); 
    
    const flatListRef = useRef();

    useEffect(() => {
        const initialize = async () => {
            try {
                const storedId = await AsyncStorage.getItem('user_id');
                if (storedId) {
                    setUserId(parseInt(storedId));
                    await fetchVets();
                } else {
                    Alert.alert("Notice", "Please log in to view messages.");
                    setLoading(false);
                }
            } catch (e) {
                setLoading(false);
            }
        };
        initialize();
    }, []);

    useEffect(() => {
        let interval;
        if (userId && selectedVet) {
            fetchMessages();
            interval = setInterval(fetchMessages, 4000);
        }
        return () => clearInterval(interval);
    }, [userId, selectedVet]);

    const fetchVets = async () => {
        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_appointment.php?action=list_owner_appointments&api_token=${token}`);
            const data = await res.json();
            if (data.success && data.vets) {
                setVets(data.vets);
                if (data.vets.length > 0 && !selectedVet) {
                    setSelectedVet(data.vets[0].id);
                }
            }
        } catch (err) { 
            console.log(err);
        } finally { 
            setLoading(false); 
        }
    };

    const fetchMessages = async () => {
        if (!selectedVet) return;
        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_messages.php?action=get_messages&other_id=${selectedVet}&api_token=${token}`);
            const data = await res.json();
            if (data.success) setMessages(data.messages);
        } catch (e) { 
            console.log(e); 
        }
    };

    const handleSend = async () => {
        const trimmedMsg = inputText.trim();
        if (!trimmedMsg || !selectedVet) return;

        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_messages.php?action=send&api_token=${token}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    receiver_id: selectedVet, 
                    message: trimmedMsg 
                })
            });
            const data = await res.json();
            if (data.success) { 
                setInputText(''); 
                fetchMessages(); 
            }
        } catch (e) { 
            Alert.alert("Error", "Network error!"); 
        }
    };

    const getCurrentVetName = () => {
        const vet = vets.find(v => v.id == selectedVet);
        return vet ? `Dr. ${vet.first_name} ${vet.last_name}` : "Select Vet";
    };

    if (loading) return (
        <View style={styles.center}><ActivityIndicator size="large" color="#6BBB77" /></View>
    );

    return (
        <SafeAreaView style={styles.safeArea}>
            <StatusBar barStyle="light-content" backgroundColor="#6BBB77" />
            
            <View style={styles.navbar}>
                <TouchableOpacity onPress={() => setShowVetList(true)}>
                    <Text style={{fontSize: 24}}>👥</Text> 
                </TouchableOpacity>
                <Text style={styles.navTitle}>{getCurrentVetName()}</Text>
                <View style={{width: 40}} /> 
            </View>

            <KeyboardAvoidingView 
                behavior={Platform.OS === 'ios' ? 'padding' : undefined} 
                style={styles.chatContainer}
                keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 0}
            >
                <FlatList
                    ref={flatListRef}
                    data={messages}
                    keyExtractor={(item) => item.id.toString()}
                    onContentSizeChange={() => flatListRef.current?.scrollToEnd({ animated: true })}
                    contentContainerStyle={{ padding: 15 }}
                    renderItem={({ item }) => (
                        <View style={[
                            styles.message, 
                            item.sender_id == userId ? styles.msgUser : styles.msgOther
                        ]}>
                            <Text style={[styles.msgText, item.sender_id == userId && { color: '#fff' }]}>
                                {item.message}
                            </Text>
                            <Text style={[styles.msgTime, item.sender_id == userId && { color: '#E8F5E9' }]}>
                                {item.created_at ? item.created_at.split(' ')[1].substring(0,5) : ""}
                            </Text>
                        </View>
                    )}
                />

                <View style={styles.sendBox}>
                    <TextInput 
                        style={styles.input}
                        value={inputText}
                        onChangeText={setInputText}
                        placeholder="Type a message..."
                        placeholderTextColor="#999"
                    />
                    <TouchableOpacity style={styles.sendBtn} onPress={handleSend}>
                        <Text style={styles.sendBtnText}>Send</Text>
                    </TouchableOpacity>
                </View>
            </KeyboardAvoidingView>

            <Modal transparent={true} visible={showVetList} animationType="fade">
                <TouchableOpacity style={styles.modalOverlay} activeOpacity={1} onPress={() => setShowVetList(false)}>
                    <View style={styles.sideDrawer}>
                        <Text style={styles.drawerTitle}>Select Veterinarian</Text>
                        <FlatList 
                            data={vets}
                            keyExtractor={(item) => item.id.toString()}
                            renderItem={({ item }) => (
                                <TouchableOpacity 
                                    style={[styles.vetItem, selectedVet == item.id && styles.vetActive]}
                                    onPress={() => {
                                        setSelectedVet(item.id);
                                        setShowVetList(false);
                                        setMessages([]); 
                                    }}
                                >
                                    <Text style={[styles.vetName, selectedVet == item.id && styles.vetNameActive]}>
                                        Dr. {item.first_name} {item.last_name}
                                    </Text>
                                </TouchableOpacity>
                            )}
                        />
                    </View>
                </TouchableOpacity>
            </Modal>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    safeArea: { flex: 1, backgroundColor: '#6BBB77' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    navbar: {
        height: 60,
        backgroundColor: '#6BBB77',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 15,
    },
    navTitle: { color: '#fff', fontSize: 18, fontWeight: 'bold' },
    chatContainer: { flex: 1, backgroundColor: '#f1f3f5' },
    message: { marginBottom: 10, maxWidth: '80%', padding: 12, borderRadius: 15 },
    msgUser: { backgroundColor: '#6BBB77', alignSelf: 'flex-end', borderBottomRightRadius: 2 },
    msgOther: { backgroundColor: '#fff', alignSelf: 'flex-start', borderBottomLeftRadius: 2, elevation: 1 },
    msgText: { fontSize: 15, color: '#333' },
    msgTime: { fontSize: 10, color: '#6c757d', marginTop: 4, alignSelf: 'flex-end' },
    sendBox: { 
        flexDirection: 'row', 
        padding: 10, 
        backgroundColor: '#fff', 
        alignItems: 'center',
        borderTopWidth: 1,
        borderTopColor: '#eee'
    },
    input: { flex: 1, backgroundColor: '#f1f3f5', borderRadius: 20, paddingHorizontal: 15, height: 40, marginRight: 10, color: '#333' },
    sendBtn: { backgroundColor: '#6BBB77', paddingVertical: 10, paddingHorizontal: 15, borderRadius: 20 },
    sendBtnText: { color: '#fff', fontWeight: 'bold' },
    modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)' },
    sideDrawer: { width: '75%', height: '100%', backgroundColor: '#fff', padding: 20, paddingTop: 50 },
    drawerTitle: { fontSize: 20, fontWeight: 'bold', marginBottom: 20, color: '#333' },
    vetItem: { paddingVertical: 15, borderBottomWidth: 1, borderBottomColor: '#f0f0f0' },
    vetActive: { backgroundColor: '#E8F5E9', borderRadius: 8, paddingHorizontal: 10 },
    vetName: { fontSize: 16, color: '#555' },
    vetNameActive: { color: '#6BBB77', fontWeight: 'bold' },
});